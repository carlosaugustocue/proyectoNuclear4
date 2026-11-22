<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Fixtures del dominio (pruebas de integración)
|--------------------------------------------------------------------------
| Insertan datos mínimos de Catálogo y Establecimientos directamente en la BD
| para que cada prueba declare explícitamente el escenario que necesita.
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** @param array<string, mixed> $attrs */
function crearProducto(array $attrs = []): string
{
    $id = (string) Str::uuid();
    DB::table('productos')->insert(array_merge([
        'id'            => $id,
        'nombre'        => 'Arroz blanco Diana',
        'marca'         => 'Diana',
        'categoria'     => 'Granos',
        'tipo_vigencia' => 'ABARROTE',
        'alias'         => json_encode([]),
        'created_at'    => now(),
        'updated_at'    => now(),
    ], $attrs));

    return $id;
}

/** @param array<string, mixed> $attrs */
function crearPresentacion(string $productoId, array $attrs = []): string
{
    $id = (string) Str::uuid();
    DB::table('presentaciones')->insert(array_merge([
        'id'                   => $id,
        'producto_id'          => $productoId,
        'descripcion'          => 'Bolsa 500 g',
        'cantidad'             => '500',
        'unidad'               => 'G',
        'unidades_por_empaque' => 1,
        'unidad_base'          => 'KG',
        'ean'                  => null,
        'created_at'           => now(),
        'updated_at'           => now(),
    ], $attrs));

    return $id;
}

/** @param array<string, mixed> $attrs */
function crearEstablecimiento(array $attrs = [], float $lat = 4.5339, float $lon = -75.6811): string
{
    $id = (string) Str::uuid();
    DB::table('establecimientos')->insert(array_merge([
        'id'         => $id,
        'nombre'     => 'Tienda Doña Rosa',
        'tipo'       => 'TIENDA_BARRIO',
        'direccion'  => 'Cra 15 # 20-10',
        'barrio'     => 'Centro',
        'municipio'  => 'Armenia',
        'ubicacion'  => DB::raw("ST_GeogFromText('POINT({$lon} {$lat})')"),
        'created_at' => now(),
        'updated_at' => now(),
    ], $attrs));

    return $id;
}

/**
 * Registra un reporte de precio por la API real (flujo de extremo a extremo).
 * En pruebas QUEUE_CONNECTION=sync: los listeners del outbox (Confianza →
 * Comparación → Moderación) se ejecutan en la misma petición.
 */
function reportarPrecio(
    string $presentacionId,
    string $establecimientoId,
    string $precio,
    string $ip = '10.0.0.1',
    ?string $observadoEn = null,
): \Illuminate\Testing\TestResponse {
    // Reporte anónimo: sin cabeceras heredadas de llamadas anteriores (p. ej., una sesión).
    return test()->flushHeaders()->withServerVariables(['REMOTE_ADDR' => $ip])
        ->withHeaders(['Idempotency-Key' => (string) Str::uuid()])
        ->postJson('/v1/reportes-precio', [
            'presentacionId'    => $presentacionId,
            'establecimientoId' => $establecimientoId,
            'precio'            => $precio,
            'observadoEn'       => $observadoEn ?? now()->subMinute()->toIso8601String(),
        ]);
}

/** Recorre recursivamente una respuesta JSON y devuelve todas sus claves. */
function clavesJson(mixed $valor): array
{
    if (! is_array($valor)) {
        return [];
    }
    $claves = [];
    foreach ($valor as $k => $v) {
        if (is_string($k)) {
            $claves[] = $k;
        }
        $claves = array_merge($claves, clavesJson($v));
    }

    return array_values(array_unique($claves));
}

/**
 * Crea una cuenta (Identidad, ADR-0025), la promueve a MODERADOR y devuelve un token de sesión.
 * Sustituye al antiguo secreto compartido MODERACION_TOKEN.
 */
function tokenDeModerador(string $correo = 'moderador@ejemplo.test'): string
{
    $registro = app(\App\Domains\Identidad\Application\GestionarCuentas::class)
        ->registrar($correo, 'frase-del-moderador-2026', '2026-11');
    app(\App\Domains\Identidad\Application\GestionarCuentas::class)->promoverAModerador($correo);

    return $registro['token'];
}

/** Crea una cuenta CIUDADANO y devuelve [cuentaId, token]. @return array{0: string, 1: string} */
function cuentaCiudadana(string $correo = 'ciudadana@ejemplo.test'): array
{
    $registro = app(\App\Domains\Identidad\Application\GestionarCuentas::class)
        ->registrar($correo, 'frase-de-la-ciudadana-2026', '2026-11');

    return [$registro['cuenta']->id, $registro['token']];
}
