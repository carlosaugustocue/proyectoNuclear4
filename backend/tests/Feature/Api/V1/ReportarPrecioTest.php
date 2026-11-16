<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/*
 * POST /v1/reportes-precio — RF-08, RF-09, RN-08, EC-07, EC-09 (contrato openapi.yaml).
 */

beforeEach(function () {
    $this->productoId      = crearProducto();
    $this->presentacionId  = crearPresentacion($this->productoId);
    $this->establecimiento = crearEstablecimiento();
});

function reporte(array $over = []): array
{
    return array_merge([
        'presentacionId'    => test()->presentacionId,
        'establecimientoId' => test()->establecimiento,
        'precio'            => '2990',
        'observadoEn'       => now()->subMinutes(3)->toIso8601String(),
    ], $over);
}

function enviar(array $cuerpo, ?string $clave = null, string $ip = '10.0.0.1')
{
    return test()->withServerVariables(['REMOTE_ADDR' => $ip])
        ->withHeaders(['Idempotency-Key' => $clave ?? (string) Str::uuid()])
        ->postJson('/v1/reportes-precio', $cuerpo);
}

describe('POST /v1/reportes-precio', function () {

    it('acepta el reporte con 202 y estado PENDIENTE_EVALUACION', function () {
        $res = enviar(reporte())->assertStatus(202);

        expect(array_keys($res->json()))->toEqualCanonicalizing(['observacionId', 'estado'])
            ->and($res->json('estado'))->toBe('PENDIENTE_EVALUACION');

        $this->assertDatabaseHas('registros_precio', [
            'id'                 => $res->json('observacionId'),
            'presentacion_id'    => $this->presentacionId,
            'producto_id'        => $this->productoId,   // derivado del catálogo
            'establecimiento_id' => $this->establecimiento,
            'monto'              => 2990.00,
            'fuente'             => 'REPORTANTE',
        ]);
    });

    it('no persiste ubicación del dispositivo (privacidad por diseño, R-01)', function () {
        enviar(reporte())->assertStatus(202);

        expect(Schema::hasColumn('registros_precio', 'ubicacion'))->toBeFalse();
    });

    it('rechaza campos no definidos en el contrato, como la ubicación del dispositivo', function () {
        enviar(reporte(['latitud' => 4.53, 'longitud' => -75.68]))
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json');
    });

    it('guarda el reportante solo como seudónimo, nunca la IP', function () {
        enviar(reporte(), null, '203.0.113.7')->assertStatus(202);

        $reportante = DB::table('registros_precio')->value('reportado_por');
        expect($reportante)->toStartWith('anon-')->not->toContain('203.0.113.7');
    });

    it('exige la cabecera Idempotency-Key en formato UUID (400)', function () {
        $this->postJson('/v1/reportes-precio', reporte())
            ->assertStatus(400)
            ->assertHeader('Content-Type', 'application/problem+json');

        $this->withHeaders(['Idempotency-Key' => 'no-es-uuid'])
            ->postJson('/v1/reportes-precio', reporte())
            ->assertStatus(400);
    });

    it('un reintento con la misma clave devuelve 200 y el mismo recurso sin duplicar (EC-09)', function () {
        $clave   = (string) Str::uuid();
        $primero = enviar(reporte(), $clave)->assertStatus(202);
        $segundo = enviar(reporte(), $clave)->assertStatus(200);

        expect($segundo->json('observacionId'))->toBe($primero->json('observacionId'))
            ->and(DB::table('registros_precio')->count())->toBe(1);
    });

    it('rechaza una presentación o un establecimiento inexistentes con 422', function () {
        enviar(reporte(['presentacionId' => (string) Str::uuid(), 'establecimientoId' => (string) Str::uuid()]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['presentacionId', 'establecimientoId']);
    });

    it('rechaza un precio que no cumple el formato Dinero del contrato', function (string $precio) {
        enviar(reporte(['precio' => $precio]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['precio']);
    })->with(['-100', '0', '12,50', '1.999', 'abc']);

    it('rechaza una observación con fecha futura con 422', function () {
        enviar(reporte(['observadoEn' => now()->addHour()->toIso8601String()]))->assertStatus(422);
    });

    it('RN-08: el mismo reportante, producto, establecimiento y día solo cuenta el último reporte', function () {
        enviar(reporte(['precio' => '2990']))->assertStatus(202);
        $ultimo = enviar(reporte(['precio' => '3050']))->assertStatus(202);

        $activos = DB::table('registros_precio')->whereNull('deleted_at')->get();
        expect($activos)->toHaveCount(1)
            ->and($activos[0]->id)->toBe($ultimo->json('observacionId'))
            ->and(DB::table('registros_precio')->count())->toBe(2); // el anterior queda como historial
    });

    it('RN-08: reportantes distintos no se reemplazan entre sí', function () {
        enviar(reporte(), null, '10.0.0.1')->assertStatus(202);
        enviar(reporte(), null, '10.0.0.2')->assertStatus(202);

        expect(DB::table('registros_precio')->whereNull('deleted_at')->count())->toBe(2);
    });

    it('RN-08: el reporte 31 de la misma hora se rechaza con 429 y Retry-After', function () {
        foreach (range(1, 30) as $i) {
            enviar(reporte(['precio' => (string) (2900 + $i)]))->assertStatus(202);
        }

        enviar(reporte())
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertHeader('Content-Type', 'application/problem+json');
    });
})->group('feature');
