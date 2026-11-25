<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Auditoría de calidad de los datos (ISO/IEC 25012) — `php artisan calidad:auditar-datos`.
 * Instrumento de M-15, M-22 y de las medidas de §9 de la especificación (D-01 a D-06). Es de solo
 * lectura para que el equipo pueda correrlo en staging y producción (ISO/IEC 25040, actividad 5.5).
 */

/** Ejecuta la auditoría y devuelve sus medidas indexadas por id (D-01…). */
function auditarDatos(): array
{
    $salida = storage_path('framework/testing/auditoria-'.Str::uuid().'.json');
    test()->artisan('calidad:auditar-datos', ['--salida' => $salida])->assertSuccessful();
    $informe = json_decode((string) file_get_contents($salida), true, flags: JSON_THROW_ON_ERROR);
    unlink($salida);

    expect($informe)->toHaveKeys(['generadoEn', 'medidas']);

    return collect($informe['medidas'])->keyBy('id')->all();
}

describe('D-01 actualidad — M-15 precios vencidos mostrados como vigentes (RN-04)', function () {
    it('cuenta lo que la comparación muestra y da 0 cuando todos los vigentes están dentro de su plazo', function () {
        $p = crearPresentacion(crearProducto());
        reportarPrecio($p, crearEstablecimiento(), '3000')->assertAccepted();
        reportarPrecio($p, crearEstablecimiento(['nombre' => 'Otra']), '3100', '10.0.0.2')->assertAccepted();

        $d01 = auditarDatos()['D-01'];

        expect($d01['medida'])->toBe('M-15')
            ->and($d01['denominador'])->toBe(2)
            ->and($d01['numerador'])->toBe(0)
            ->and($d01['valor'])->toEqual(0)
            ->and($d01['cumple'])->toBeTrue();
    });

    it('un precio fuera de plazo que se muestra lo hace con advertencia y no cuenta como violación', function () {
        $p = crearPresentacion(crearProducto(['tipo_vigencia' => 'PERECEDERO']));
        reportarPrecio($p, crearEstablecimiento(), '3000')->assertAccepted();
        // 5 días para un perecedero (plazo 3): «puede estar desactualizado», se muestra con advertencia.
        DB::table('comparacion_vigente')->update(['observado_en' => now()->subDays(5)]);

        $d01 = auditarDatos()['D-01'];

        expect($d01['numerador'])->toBe(0)
            ->and($d01['detalle']['fueraDePlazoConAdvertencia'])->toBe(1);
    });

    it('un precio en historial (más del doble del plazo) no se muestra como vigente', function () {
        $p = crearPresentacion(crearProducto());
        reportarPrecio($p, crearEstablecimiento(), '3000')->assertAccepted();
        DB::table('comparacion_vigente')->update(['observado_en' => now()->subDays(20)]);

        expect(auditarDatos()['D-01']['denominador'])->toBe(0);
    });
});

describe('D-02 completitud de las presentaciones', function () {
    it('es la proporción de presentaciones con cantidad, unidad y unidad base válidas', function () {
        $producto = crearProducto();
        crearPresentacion($producto);
        crearPresentacion($producto, ['descripcion' => 'Bolsa 1 kg', 'cantidad' => '1', 'unidad' => 'KG']);
        crearPresentacion($producto, ['descripcion' => 'Sin cantidad', 'cantidad' => '0']);

        $d02 = auditarDatos()['D-02'];

        expect($d02['numerador'])->toBe(2)
            ->and($d02['denominador'])->toBe(3)
            ->and($d02['cumple'])->toBeFalse();
    });
});

describe('D-03 trazabilidad de las observaciones', function () {
    it('es la proporción de observaciones con fuente, fecha observada y fecha de registro', function () {
        $p = crearPresentacion(crearProducto());
        $id = reportarPrecio($p, crearEstablecimiento(), '3000')->json('observacionId');
        reportarPrecio($p, crearEstablecimiento(['nombre' => 'Otra']), '3100', '10.0.0.2')->assertAccepted();
        DB::table('registros_precio')->where('id', $id)->update(['observado_en' => null]);

        $d03 = auditarDatos()['D-03'];

        expect($d03['numerador'])->toBe(1)
            ->and($d03['denominador'])->toBe(2)
            ->and($d03['cumple'])->toBeFalse();
    });
});

describe('D-04 consistencia del catálogo', function () {
    it('cuenta como duplicado el mismo nombre y marca aunque cambien mayúsculas, tildes o espacios', function () {
        crearProducto(['nombre' => 'Café molido', 'marca' => 'Águila']);
        crearProducto(['nombre' => ' cafe MOLIDO ', 'marca' => 'aguila']);
        crearProducto(['nombre' => 'Café molido', 'marca' => 'Sello Rojo']);
        crearProducto(['nombre' => 'Panela', 'marca' => null]);

        $d04 = auditarDatos()['D-04'];

        expect($d04['numerador'])->toBe(1)
            ->and($d04['denominador'])->toBe(4)
            ->and($d04['valor'])->toEqual(0.25)
            ->and($d04['cumple'])->toBeFalse(); // umbral ≤ 0,02
    });
});

describe('D-05 precios oficiales no confirmados — M-22 (ADR-0015)', function () {
    it('es la proporción de precios oficiales que no quedaron CONFIABLE', function () {
        $p = crearPresentacion(crearProducto());
        $a = reportarPrecio($p, crearEstablecimiento(), '3000')->json('observacionId');
        $b = reportarPrecio($p, crearEstablecimiento(['nombre' => 'Otra']), '3100', '10.0.0.2')->json('observacionId');
        DB::table('registros_precio')->whereIn('id', [$a, $b])->update(['fuente' => 'COMERCIO']);
        DB::table('evaluaciones_confianza')->where('observacion_id', $a)->update(['estado' => 'CONFIABLE']);
        DB::table('evaluaciones_confianza')->where('observacion_id', $b)->update(['estado' => 'NO_CORROBORADO']);

        $d05 = auditarDatos()['D-05'];

        expect($d05['medida'])->toBe('M-22')
            ->and($d05['numerador'])->toBe(1)
            ->and($d05['denominador'])->toBe(2)
            ->and($d05['cumple'])->toBeFalse(); // umbral ≤ 0,10
    });

    it('sin precios oficiales publicados no hay medida (no se reporta un 0 engañoso)', function () {
        $d05 = auditarDatos()['D-05'];

        expect($d05['denominador'])->toBe(0)
            ->and($d05['valor'])->toBeNull()
            ->and($d05['cumple'])->toBeNull();
    });
});

describe('D-06 confidencialidad — sin ubicación ni rastros del dispositivo persistidos (EC-07, R-01)', function () {
    it('el esquema real no guarda coordenadas fuera de establecimientos ni IP o user agent de los usuarios', function () {
        $d06 = auditarDatos()['D-06'];

        expect($d06['numerador'])->toBe(0)
            ->and($d06['detalle']['columnas'])->toBe([])
            ->and($d06['cumple'])->toBeTrue();
    });
});

describe('la auditoría es de solo lectura', function () {
    it('no escribe en ninguna tabla y se puede ejecutar en producción', function () {
        $p = crearPresentacion(crearProducto());
        reportarPrecio($p, crearEstablecimiento(), '3000')->assertAccepted();
        $antes = DB::table('registros_precio')->count() + DB::table('comparacion_vigente')->count() + DB::table('productos')->count();
        app()->detectEnvironment(fn () => 'production');

        auditarDatos();

        app()->detectEnvironment(fn () => 'testing');
        expect(DB::table('registros_precio')->count() + DB::table('comparacion_vigente')->count() + DB::table('productos')->count())->toBe($antes);
    });
});
