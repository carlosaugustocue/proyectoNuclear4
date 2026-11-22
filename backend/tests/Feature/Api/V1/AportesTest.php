<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Aportes con cuenta (ADR-0025, ADR-0026):
 *  - RF-10 confirmar «sigue igual»: es una observación nueva del mismo precio a nombre de quien
 *    confirma, y pasa por las mismas reglas de confianza (RN-05 corroboración, RN-08 límite).
 *  - RF-19 marcar un precio como dudoso: abre un caso de moderación (MARCA_USUARIO).
 *  - RF-12 mis aportes: mis observaciones y su estado, con mi reputación (RN-07).
 * C-20: contrato de /v1/observaciones/{id}/confirmaciones, /v1/observaciones/{id}/marcas y /v1/mis-aportes.
 */

beforeEach(function () {
    $this->arroz  = crearProducto(['nombre' => 'Arroz blanco']);
    $this->bolsa  = crearPresentacion($this->arroz, ['descripcion' => 'Bolsa 1 kg', 'cantidad' => '1', 'unidad' => 'KG']);
    $this->tienda = crearEstablecimiento(['nombre' => 'Tienda Don Pepe']);
});

/** withHeaders acumula cabeceras entre llamadas: se limpian para que cada petición lleve solo las suyas. */
function con(string $token): \Illuminate\Foundation\Testing\TestCase
{
    return test()->flushHeaders()->withHeaders(['Authorization' => "Bearer {$token}"]);
}

function confirmar(string $token, string $observacionId, ?string $clave = null): \Illuminate\Testing\TestResponse
{
    return con($token)->withHeaders(['Idempotency-Key' => $clave ?? (string) Str::uuid()])
        ->postJson("/v1/observaciones/{$observacionId}/confirmaciones");
}

function itemDeTienda(string $productoId, string $nombre): ?array
{
    return collect(test()->getJson("/v1/comparaciones?productoId={$productoId}")->json('items'))->firstWhere('establecimiento.nombre', $nombre);
}

describe('POST /v1/observaciones/{id}/confirmaciones (RF-10)', function () {
    it('confirmar el precio de otro lo corrobora: pasa a CONFIABLE (RN-05)', function () {
        $obs = reportarPrecio($this->bolsa, $this->tienda, '3000')->json('observacionId');
        [$cuentaId, $token] = cuentaCiudadana();

        $nueva = confirmar($token, $obs)->assertAccepted()->json('observacionId');

        expect($nueva)->not->toBe($obs)
            ->and(DB::table('registros_precio')->where('id', $nueva)->first(['monto', 'reportado_por', 'establecimiento_id']))
            ->toMatchArray(['monto' => '3000.00', 'reportado_por' => $cuentaId, 'establecimiento_id' => $this->tienda]);
        $item = itemDeTienda($this->arroz, 'Tienda Don Pepe');
        expect($item['estadoConfianza'])->toBe('CONFIABLE')->and($item['confirmaciones'])->toBeGreaterThanOrEqual(1);
    });

    it('exige sesión', function () {
        $obs = reportarPrecio($this->bolsa, $this->tienda, '3000')->json('observacionId');
        $this->flushHeaders()->withHeaders(['Idempotency-Key' => (string) Str::uuid()])->postJson("/v1/observaciones/{$obs}/confirmaciones")->assertUnauthorized();
    });

    it('no se puede confirmar un precio propio: 409', function () {
        [, $token] = cuentaCiudadana();
        $obs = con($token)->withHeaders(['Idempotency-Key' => (string) Str::uuid()])->postJson('/v1/reportes-precio', [
            'presentacionId' => $this->bolsa, 'establecimientoId' => $this->tienda, 'precio' => '3000', 'observadoEn' => now()->subMinute()->toIso8601String(),
        ])->json('observacionId');

        confirmar($token, $obs)->assertStatus(409);
    });

    it('solo se confirma el precio más reciente de ese establecimiento: un precio superado da 409', function () {
        $vieja = reportarPrecio($this->bolsa, $this->tienda, '3000', '10.0.0.1', now()->subHours(3)->toIso8601String())->json('observacionId');
        reportarPrecio($this->bolsa, $this->tienda, '3500', '10.0.0.2');
        [, $token] = cuentaCiudadana();

        confirmar($token, $vieja)->assertStatus(409);
    });

    it('no se puede confirmar un precio invalidado por moderación: 409', function () {
        $obs = reportarPrecio($this->bolsa, $this->tienda, '3000')->json('observacionId');
        DB::table('evaluaciones_confianza')->where('observacion_id', $obs)->update(['estado' => 'NO_CONFIABLE']);
        [, $token] = cuentaCiudadana();

        confirmar($token, $obs)->assertStatus(409);
    });

    it('es idempotente: la misma Idempotency-Key devuelve 200 con la misma observación (EC-09)', function () {
        $obs = reportarPrecio($this->bolsa, $this->tienda, '3000')->json('observacionId');
        [, $token] = cuentaCiudadana();
        $clave = (string) Str::uuid();

        $primera = confirmar($token, $obs, $clave)->assertAccepted()->json('observacionId');
        expect(confirmar($token, $obs, $clave)->assertOk()->json('observacionId'))->toBe($primera);
    });

    it('sin Idempotency-Key responde 400; observación inexistente 404', function () {
        $obs = reportarPrecio($this->bolsa, $this->tienda, '3000')->json('observacionId');
        [, $token] = cuentaCiudadana();

        con($token)->postJson("/v1/observaciones/{$obs}/confirmaciones")->assertStatus(400);
        confirmar($token, (string) Str::uuid())->assertNotFound();
    });
});

describe('POST /v1/observaciones/{id}/marcas (RF-19)', function () {
    it('marcar abre un caso MARCA_USUARIO con el motivo, sin decir quién marcó', function () {
        $obs = reportarPrecio($this->bolsa, $this->tienda, '3000')->json('observacionId');
        [$cuentaId, $token] = cuentaCiudadana();

        con($token)->postJson("/v1/observaciones/{$obs}/marcas", ['motivo' => 'PRECIO_INCORRECTO', 'comentario' => 'En la góndola dice 3.500'])
            ->assertCreated();

        $caso = DB::table('casos_moderacion')->where('observacion_id', $obs)->where('origen', 'MARCA_USUARIO')->first();
        expect($caso->estado)->toBe('ABIERTO')
            ->and($caso->detalle)->toContain('Precio incorrecto')->toContain('En la góndola dice 3.500')
            ->and($caso->detalle)->not->toContain($cuentaId);
    });

    it('cada cuenta marca una sola vez; varias marcas comparten un solo caso', function () {
        $obs = reportarPrecio($this->bolsa, $this->tienda, '3000')->json('observacionId');
        [, $ana]   = cuentaCiudadana('ana@ejemplo.test');
        [, $berta] = cuentaCiudadana('berta@ejemplo.test');

        con($ana)->postJson("/v1/observaciones/{$obs}/marcas", ['motivo' => 'DESACTUALIZADO'])->assertCreated();
        con($ana)->postJson("/v1/observaciones/{$obs}/marcas", ['motivo' => 'DESACTUALIZADO'])->assertStatus(409);
        con($berta)->postJson("/v1/observaciones/{$obs}/marcas", ['motivo' => 'OTRO'])->assertCreated();

        expect(DB::table('casos_moderacion')->where('observacion_id', $obs)->where('origen', 'MARCA_USUARIO')->count())->toBe(1)
            ->and(DB::table('marcas_observacion')->where('observacion_id', $obs)->count())->toBe(2);
    });

    it('exige sesión, valida el motivo y responde 404 si la observación no existe', function () {
        $obs = reportarPrecio($this->bolsa, $this->tienda, '3000')->json('observacionId');
        [, $token] = cuentaCiudadana();

        $this->flushHeaders()->postJson("/v1/observaciones/{$obs}/marcas", ['motivo' => 'OTRO'])->assertUnauthorized();
        con($token)->postJson("/v1/observaciones/{$obs}/marcas", ['motivo' => 'ME_CAE_MAL', 'comentario' => str_repeat('x', 281)])
            ->assertStatus(422)->assertJsonValidationErrors(['motivo', 'comentario']);
        con($token)->postJson('/v1/observaciones/' . Str::uuid() . '/marcas', ['motivo' => 'OTRO'])->assertNotFound();
    });

    it('la marca no cambia la comparación: el moderador decide (ADR-0007, marcar no borrar)', function () {
        $obs = reportarPrecio($this->bolsa, $this->tienda, '3000')->json('observacionId');
        [, $token] = cuentaCiudadana();
        $antes = itemDeTienda($this->arroz, 'Tienda Don Pepe');

        con($token)->postJson("/v1/observaciones/{$obs}/marcas", ['motivo' => 'PRECIO_INCORRECTO'])->assertCreated();

        expect(itemDeTienda($this->arroz, 'Tienda Don Pepe'))->toBe($antes);
    });
});

describe('GET /v1/mis-aportes (RF-12)', function () {
    it('lista solo mis aportes, el más reciente primero, con su estado y mi reputación', function () {
        [, $token] = cuentaCiudadana();
        $otra = crearEstablecimiento(['nombre' => 'Supermercado B']);
        foreach ([[$this->tienda, '3000', 30], [$otra, '3200', 5]] as [$est, $precio, $minutos]) {
            con($token)->withHeaders(['Idempotency-Key' => (string) Str::uuid()])->postJson('/v1/reportes-precio', [
                'presentacionId' => $this->bolsa, 'establecimientoId' => $est, 'precio' => $precio,
                'observadoEn' => now()->subMinutes($minutos)->toIso8601String(),
            ])->assertAccepted();
        }
        reportarPrecio($this->bolsa, $this->tienda, '9999', '10.9.9.9'); // de otra persona

        con($token)->getJson('/v1/mis-aportes')->assertOk()
            ->assertJsonPath('pagina.total', 2)
            ->assertJsonPath('items.0.establecimiento', 'Supermercado B')
            ->assertJsonPath('items.0.producto', 'Arroz blanco')
            ->assertJsonPath('items.0.presentacion', 'Bolsa 1 kg')
            ->assertJsonPath('items.0.precio', '3200.00')
            ->assertJsonPath('items.0.estadoConfianza', 'NO_CORROBORADO')
            ->assertJsonPath('items.1.precio', '3000.00')
            ->assertJsonPath('reputacion.aportesConfirmados', 0)
            ->assertJsonPath('reputacion.requiereCorroboracion', true);
    });

    it('exige sesión y no expone identificadores del reportante (EC-07)', function () {
        $this->flushHeaders()->getJson('/v1/mis-aportes')->assertUnauthorized();

        [$cuentaId, $token] = cuentaCiudadana();
        $r = con($token)->getJson('/v1/mis-aportes')->assertOk()->assertJsonPath('items', []);
        expect(clavesJson($r->json()))->not->toContain('reportadoPor')
            ->and(json_encode($r->json()))->not->toContain($cuentaId);
    });
});
