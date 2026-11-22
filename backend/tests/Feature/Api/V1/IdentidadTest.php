<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

/*
 * Identidad — RF-22 (registro con autorización de datos, Ley 1581), RF-23 (consultar, actualizar
 * y suprimir), RF-24 (roles), ADR-0025 (sesión con token Bearer opaco).
 * C-19: contrato de /v1/cuentas y /v1/sesiones.
 */

const CONTRASENA = 'una-frase-larga-2026';

function registrar(string $correo = 'ana@ejemplo.test', array $extra = []): \Illuminate\Testing\TestResponse
{
    return test()->postJson('/v1/cuentas', array_merge([
        'correo' => $correo, 'contrasena' => CONTRASENA, 'aceptaTratamientoDatos' => true, 'versionPolitica' => '2026-11',
    ], $extra));
}

function iniciarSesion(string $correo = 'ana@ejemplo.test', string $contrasena = CONTRASENA): \Illuminate\Testing\TestResponse
{
    return test()->postJson('/v1/sesiones', ['correo' => $correo, 'contrasena' => $contrasena]);
}

function conToken(string $token): \Illuminate\Foundation\Testing\TestCase
{
    return test()->withHeaders(['Authorization' => "Bearer {$token}"]);
}

describe('POST /v1/cuentas (RF-22)', function () {
    it('crea la cuenta con el consentimiento registrado y abre una sesión', function () {
        registrar(' Ana@Ejemplo.TEST ')
            ->assertCreated()
            ->assertJsonPath('cuenta.rol', 'CIUDADANO')
            ->assertJsonStructure(['cuenta' => ['id', 'seudonimo', 'rol'], 'sesion' => ['token', 'expiraEn']]);

        $fila = DB::table('cuentas')->first();
        expect($fila->correo)->toBe('ana@ejemplo.test')
            ->and($fila->version_politica)->toBe('2026-11')
            ->and($fila->consentimiento_en)->not->toBeNull()
            ->and($fila->contrasena_hash)->not->toContain(CONTRASENA)
            ->and(password_verify(CONTRASENA, $fila->contrasena_hash))->toBeTrue();
    });

    it('sin autorización expresa de tratamiento de datos no crea la cuenta (Ley 1581)', function () {
        registrar(extra: ['aceptaTratamientoDatos' => false])->assertStatus(422)->assertJsonValidationErrors(['aceptaTratamientoDatos']);
        expect(DB::table('cuentas')->count())->toBe(0);
    });

    it('devuelve todos los motivos de una contraseña débil', function () {
        registrar(extra: ['contrasena' => '12345'])->assertStatus(422)
            ->assertJsonPath('errors.contrasena', ['Debe tener al menos 10 caracteres.', 'No puede ser solo números.']);
    });

    it('rechaza un correo con salto de línea (CRLF, GHSA-5vg9-5847-vvmq)', function () {
        registrar("ana@ejemplo.test\r\nBcc: otro@ejemplo.test")->assertStatus(422)->assertJsonValidationErrors(['correo']);
    });

    it('un correo ya registrado responde 409', function () {
        registrar()->assertCreated();
        registrar('ANA@ejemplo.test')->assertStatus(409);
    });

    it('nunca devuelve el correo ni el hash en la respuesta de registro', function () {
        $texto = json_encode(registrar()->json());
        expect($texto)->not->toContain('ana@ejemplo.test')->not->toContain('contrasena');
    });
});

describe('POST /v1/sesiones y DELETE /v1/sesiones/actual (ADR-0025)', function () {
    it('inicia sesión con correo y contraseña y el token sirve para /cuentas/yo', function () {
        registrar();
        $token = iniciarSesion('ANA@ejemplo.test')->assertOk()->json('sesion.token');

        conToken($token)->getJson('/v1/cuentas/yo')->assertOk()
            ->assertJsonPath('correo', 'ana@ejemplo.test')
            ->assertJsonPath('rol', 'CIUDADANO')
            ->assertJsonPath('consentimiento.versionPolitica', '2026-11');
    });

    it('credenciales incorrectas o correo inexistente dan el mismo 401 genérico', function () {
        registrar();
        $a = iniciarSesion('ana@ejemplo.test', 'otra-frase-larga')->assertUnauthorized()->json('detail');
        $b = iniciarSesion('nadie@ejemplo.test')->assertUnauthorized()->json('detail');
        expect($a)->toBe($b);
    });

    it('guarda solo el hash del token y vence a las 8 horas', function () {
        registrar();
        $token = iniciarSesion()->json('sesion.token');
        $sesion = DB::table('sesiones')->first();

        expect($sesion->token_hash)->toBe(hash('sha256', $token))
            ->and(abs(strtotime($sesion->expira_en) - strtotime('+8 hours')))->toBeLessThan(60);

        $this->travel(8)->hours();
        $this->travel(1)->minutes();
        conToken($token)->getJson('/v1/cuentas/yo')->assertUnauthorized();
    });

    it('cerrar sesión revoca el token', function () {
        $token = registrar()->json('sesion.token');

        conToken($token)->deleteJson('/v1/sesiones/actual')->assertNoContent();
        conToken($token)->getJson('/v1/cuentas/yo')->assertUnauthorized();
    });

    it('limita los intentos de inicio de sesión (fuerza bruta): 5 por minuto por IP y correo', function () {
        registrar();
        foreach (range(1, 5) as $_) {
            iniciarSesion('ana@ejemplo.test', 'otra-frase-larga')->assertUnauthorized();
        }
        iniciarSesion()->assertStatus(429);
    });

    it('sin token o con un token inventado, /cuentas/yo responde 401', function () {
        $this->getJson('/v1/cuentas/yo')->assertUnauthorized();
        conToken(str_repeat('a', 64))->getJson('/v1/cuentas/yo')->assertUnauthorized();
    });
});

describe('RF-23 consultar, actualizar y suprimir los datos', function () {
    it('cambia la contraseña con la actual y cierra las demás sesiones', function () {
        $token = registrar()->json('sesion.token');
        $otra  = iniciarSesion()->json('sesion.token');

        conToken($token)->putJson('/v1/cuentas/yo/contrasena', ['actual' => CONTRASENA, 'nueva' => 'otra-frase-mas-larga'])->assertNoContent();

        conToken($otra)->getJson('/v1/cuentas/yo')->assertUnauthorized();
        iniciarSesion('ana@ejemplo.test', 'otra-frase-mas-larga')->assertOk();
    });

    it('no cambia la contraseña si la actual es incorrecta', function () {
        $token = registrar()->json('sesion.token');
        conToken($token)->putJson('/v1/cuentas/yo/contrasena', ['actual' => 'no-es-la-actual', 'nueva' => 'otra-frase-mas-larga'])->assertStatus(422);
    });

    it('suprimir la cuenta borra los datos personales y conserva los aportes anonimizados', function () {
        $token = registrar()->json('sesion.token');
        $cuentaId = DB::table('cuentas')->value('id');
        $p = crearPresentacion(crearProducto());
        $obs = conToken($token)->withHeaders(['Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()])
            ->postJson('/v1/reportes-precio', ['presentacionId' => $p, 'establecimientoId' => crearEstablecimiento(), 'precio' => '3000', 'observadoEn' => now()->subMinute()->toIso8601String()])
            ->assertAccepted()->json('observacionId');

        conToken($token)->deleteJson('/v1/cuentas/yo')->assertNoContent();

        expect(DB::table('cuentas')->where('id', $cuentaId)->exists())->toBeFalse()
            ->and(DB::table('sesiones')->count())->toBe(0)
            ->and(DB::table('registros_precio')->where('id', $obs)->exists())->toBeTrue();
        iniciarSesion()->assertUnauthorized();
    });
});

describe('Reportes con cuenta (RN-07 por cuenta, no por IP)', function () {
    it('un reporte con sesión queda asociado a la cuenta, no a la IP', function () {
        $token = registrar()->json('sesion.token');
        $cuentaId = DB::table('cuentas')->value('id');
        $p = crearPresentacion(crearProducto());

        $obs = conToken($token)->withHeaders(['Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()])
            ->postJson('/v1/reportes-precio', ['presentacionId' => $p, 'establecimientoId' => crearEstablecimiento(), 'precio' => '3000', 'observadoEn' => now()->subMinute()->toIso8601String()])
            ->assertAccepted()->json('observacionId');

        expect(DB::table('registros_precio')->where('id', $obs)->value('reportado_por'))->toBe($cuentaId);
    });

    it('un token inválido al reportar responde 401 (no cae silenciosamente en anónimo)', function () {
        $p = crearPresentacion(crearProducto());
        conToken(str_repeat('b', 64))->withHeaders(['Idempotency-Key' => (string) \Illuminate\Support\Str::uuid()])
            ->postJson('/v1/reportes-precio', ['presentacionId' => $p, 'establecimientoId' => crearEstablecimiento(), 'precio' => '3000', 'observadoEn' => now()->subMinute()->toIso8601String()])
            ->assertUnauthorized();
    });
});
