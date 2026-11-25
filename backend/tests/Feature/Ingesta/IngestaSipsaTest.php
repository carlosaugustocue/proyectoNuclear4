<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Ingesta de SIPSA-DANE (RF-25, RF-26, ADR-0010): `php artisan ingesta:sipsa [archivo]`.
 * Alimenta el catálogo con los productos que se venden en Armenia y guarda los precios mayoristas
 * como REFERENCIA, aparte: nunca entran a la comparación de tiendas ni crean observaciones de precio.
 */

/** Escribe un CSV con el formato de scripts/sipsa/extraer_armenia.py y devuelve su ruta. */
function csvSipsa(string ...$filas): string
{
    $ruta = storage_path('framework/testing/sipsa-'.Str::uuid().'.csv');
    file_put_contents($ruta, "fecha,grupo,producto,codigo_cpc,mercado,precio\n".implode("\n", $filas)."\n");
    test()->beforeApplicationDestroyed(static fn () => @unlink($ruta));

    return $ruta;
}

function productoPorNombre(string $nombre): ?object
{
    return DB::table('productos')->where('nombre', $nombre)->first();
}

describe('ingesta:sipsa — catálogo', function () {
    it('crea los productos con su categoría, vigencia y una presentación en su unidad base', function () {
        $csv = csvSipsa(
            '2026-10-01,VERDURAS Y HORTALIZAS,Zanahoria,0123901,"Armenia, Mercar",1797',
            '2026-10-01,LACTEOS Y HUEVOS,Huevo rojo AA,0231101,"Armenia, Mercar",435',
            '2026-10-01,PROCESADOS,Aceite vegetal mezcla,2163001,"Armenia, Mercar",10803',
        );

        $this->artisan('ingesta:sipsa', ['archivo' => $csv])->assertSuccessful();

        $zanahoria = productoPorNombre('Zanahoria');
        expect($zanahoria->categoria)->toBe('Frutas y verduras')
            ->and($zanahoria->tipo_vigencia)->toBe('PERECEDERO')
            ->and($zanahoria->marca)->toBeNull()
            ->and($zanahoria->estado_catalogo)->toBe('APROBADO');

        $presentacion = fn (string $nombre) => (array) DB::table('presentaciones')->where('producto_id', productoPorNombre($nombre)->id)->sole();
        expect($presentacion('Zanahoria'))->toMatchArray(['descripcion' => 'Por kilo', 'unidad' => 'KG', 'unidad_base' => 'KG'])
            ->and($presentacion('Huevo rojo AA'))->toMatchArray(['descripcion' => 'Unidad', 'unidad' => 'UNIDAD', 'unidad_base' => 'UNIDAD'])
            ->and($presentacion('Aceite vegetal mezcla'))->toMatchArray(['descripcion' => 'Por litro', 'unidad' => 'L', 'unidad_base' => 'L'])
            ->and((float) $presentacion('Zanahoria')['cantidad'])->toBe(1.0);
    });

    it('reutiliza el producto genérico que ya existe (mismo nombre sin marca) y no crea duplicados (D-04)', function () {
        $existente = crearProducto(['nombre' => 'Tomate chonto', 'marca' => null, 'categoria' => 'Frutas y verduras']);
        crearPresentacion($existente, ['descripcion' => 'Por kilo', 'cantidad' => '1', 'unidad' => 'KG']);

        $this->artisan('ingesta:sipsa', ['archivo' => csvSipsa('2026-10-01,VERDURAS Y HORTALIZAS,tomate  CHONTO,0123401,"Armenia, Mercar",4052')])
            ->assertSuccessful();

        expect(DB::table('productos')->count())->toBe(1)
            ->and(DB::table('presentaciones')->count())->toBe(1)
            ->and(DB::table('referencias_mayoristas')->value('producto_id'))->toBe($existente);
    });

    it('un producto de marca no es el genérico de SIPSA: crea el genérico aparte', function () {
        crearProducto(['nombre' => 'Café molido', 'marca' => 'Del Quindío']);

        $this->artisan('ingesta:sipsa', ['archivo' => csvSipsa('2026-10-01,PROCESADOS,Café molido,2391101,"Armenia, Mercar",68824')])
            ->assertSuccessful();

        expect(DB::table('productos')->where('nombre', 'Café molido')->whereNull('marca')->count())->toBe(1)
            ->and(DB::table('productos')->count())->toBe(2);
    });

    it('es idempotente: correrla dos veces no duplica productos ni referencias y actualiza el precio', function () {
        $this->artisan('ingesta:sipsa', ['archivo' => csvSipsa('2026-10-01,FRUTAS,Lulo,0134901,"Armenia, Mercar",4247')])->assertSuccessful();
        $this->artisan('ingesta:sipsa', ['archivo' => csvSipsa('2026-10-01,FRUTAS,Lulo,0134901,"Armenia, Mercar",4300')])->assertSuccessful();

        expect(DB::table('productos')->count())->toBe(1)
            ->and(DB::table('referencias_mayoristas')->count())->toBe(1)
            ->and((float) DB::table('referencias_mayoristas')->value('precio'))->toBe(4300.0);
    });

    it('omite y reporta las filas inválidas sin detener el resto de la carga', function () {
        $csv = csvSipsa(
            '2026-10-01,FRUTAS,Lulo,0134901,"Armenia, Mercar",4247',
            '2026-10-01,FLORES,Rosa,0000000,"Armenia, Mercar",900',
            '2026-10-01,FRUTAS,Fresa,0134201,"Armenia, Mercar",0',
        );

        $this->artisan('ingesta:sipsa', ['archivo' => $csv])
            ->expectsOutputToContain('2 filas rechazadas')
            ->assertSuccessful();

        expect(DB::table('productos')->pluck('nombre')->all())->toBe(['Lulo']);
    });

    it('falla con un mensaje claro si el archivo no existe o no tiene las columnas esperadas', function () {
        $this->artisan('ingesta:sipsa', ['archivo' => '/no/existe.csv'])->assertFailed();

        $sinColumnas = storage_path('framework/testing/sipsa-malo.csv');
        file_put_contents($sinColumnas, "producto,precio\nLulo,4247\n");
        $this->artisan('ingesta:sipsa', ['archivo' => $sinColumnas])->expectsOutputToContain('columnas')->assertFailed();
        unlink($sinColumnas);
    });

    it('por defecto carga el CSV versionado de Armenia (115 productos)', function () {
        $this->artisan('ingesta:sipsa')->assertSuccessful();

        expect(DB::table('productos')->count())->toBe(115)
            ->and(DB::table('referencias_mayoristas')->count())->toBe(1198);
    });
});

describe('ADR-0010 — la referencia mayorista va aparte de la comparación', function () {
    it('no crea observaciones de precio ni filas en el modelo de lectura de la comparación', function () {
        $this->artisan('ingesta:sipsa', ['archivo' => csvSipsa('2026-10-01,FRUTAS,Lulo,0134901,"Armenia, Mercar",4247')])->assertSuccessful();

        expect(DB::table('registros_precio')->count())->toBe(0)
            ->and(DB::table('comparacion_vigente')->count())->toBe(0);

        $this->getJson('/v1/comparaciones?productoId='.productoPorNombre('Lulo')->id)
            ->assertOk()
            ->assertJsonCount(0, 'items');
    });
});

describe('GET /v1/productos/{id}/referencia-mayorista', function () {
    it('devuelve la serie mensual (promedio de los mercados de Armenia), el último mes y la fuente', function () {
        $this->artisan('ingesta:sipsa', ['archivo' => csvSipsa(
            '2026-09-01,FRUTAS,Banano criollo,0131201,"Armenia, Mercar",1400',
            '2026-10-01,FRUTAS,Banano criollo,0131201,"Armenia, Mercar",1870',
            '2026-10-01,FRUTAS,Banano criollo,0131201,"Armenia, Retiro",1708',
        )])->assertSuccessful();
        $id = productoPorNombre('Banano criollo')->id;

        $this->getJson("/v1/productos/{$id}/referencia-mayorista")
            ->assertOk()
            ->assertJsonPath('productoId', $id)
            ->assertJsonPath('unidadBase', 'KG')
            ->assertJsonPath('serie', [['mes' => '2026-09', 'precio' => '1400'], ['mes' => '2026-10', 'precio' => '1789']])
            ->assertJsonPath('ultimo.mes', '2026-10')
            ->assertJsonPath('ultimo.precio', '1789')
            ->assertJsonPath('mercados', ['Armenia, Mercar', 'Armenia, Retiro'])
            ->assertJsonPath('fuente.nombre', 'SIPSA – DANE')
            ->assertJson(fn ($json) => $json->whereType('advertencia', 'string')->etc());
    });

    it('un producto sin referencia responde 200 con la serie vacía', function () {
        $id = crearProducto();

        $this->getJson("/v1/productos/{$id}/referencia-mayorista")
            ->assertOk()
            ->assertJsonPath('serie', [])
            ->assertJsonPath('ultimo', null);
    });

    it('un producto inexistente responde 404 en Problem Details', function () {
        $this->getJson('/v1/productos/'.Str::uuid().'/referencia-mayorista')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json');
    });
});
