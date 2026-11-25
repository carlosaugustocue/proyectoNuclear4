<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/*
 * Fotos del catálogo: `php artisan catalogo:imagenes [archivo]` aplica las fotos con licencia libre que
 * vienen empacadas con la PWA (frontend/public/productos/) a los productos que aún no tienen foto.
 * Se ejecuta al arrancar la API, después de la ingesta de SIPSA, así que debe ser idempotente.
 */

function csvImagenes(string ...$filas): string
{
    $ruta = storage_path('framework/testing/imagenes-'.Str::uuid().'.csv');
    file_put_contents($ruta, "producto,archivo,credito\n".implode("\n", $filas)."\n");
    test()->beforeApplicationDestroyed(static fn () => @unlink($ruta));

    return $ruta;
}

function productoParaFoto(string $nombre, ?string $marca = null, ?string $imagen = null): string
{
    $id = (string) Str::uuid();
    DB::table('productos')->insert([
        'id' => $id, 'nombre' => $nombre, 'marca' => $marca, 'categoria' => 'Frutas y verduras',
        'tipo_vigencia' => 'PERECEDERO', 'estado_catalogo' => 'APROBADO', 'imagen_url' => $imagen,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    return $id;
}

function fotoDe(string $id): object
{
    return DB::table('productos')->where('id', $id)->first(['imagen_url', 'imagen_credito']);
}

describe('catalogo:imagenes', function () {
    it('asigna la ruta local y el crédito a los productos sin foto, con o sin marca', function () {
        $lulo = productoParaFoto('Lulo');
        $cafe = productoParaFoto('Café molido');
        $cafeMarca = productoParaFoto('Café molido', 'Del Quindío');
        $csv = csvImagenes(
            'Lulo,lulo.webp,"Foto: Ana Pérez, Wikimedia Commons (CC BY-SA 4.0)"',
            'Café molido,cafe-molido.webp,"Foto: Juan Ruiz, Wikimedia Commons (CC0)"',
        );

        $this->artisan('catalogo:imagenes', ['archivo' => $csv])->assertSuccessful();

        expect(fotoDe($lulo)->imagen_url)->toBe('/productos/lulo.webp')
            ->and(fotoDe($lulo)->imagen_credito)->toBe('Foto: Ana Pérez, Wikimedia Commons (CC BY-SA 4.0)')
            ->and(fotoDe($cafe)->imagen_url)->toBe('/productos/cafe-molido.webp')
            ->and(fotoDe($cafeMarca)->imagen_url)->toBe('/productos/cafe-molido.webp');
    });

    it('no reemplaza una foto que el producto ya tiene (Open Food Facts)', function () {
        $url = 'https://images.openfoodfacts.org/images/products/770/251/100/0014/front_en.30.400.jpg';
        $arroz = productoParaFoto('Arroz blanco', 'Diana', $url);

        $this->artisan('catalogo:imagenes', ['archivo' => csvImagenes('Arroz blanco,arroz-blanco.webp,Foto: X (CC0)')])
            ->assertSuccessful();

        expect(fotoDe($arroz)->imagen_url)->toBe($url);
    });

    it('es idempotente e ignora productos que no existen en el catálogo', function () {
        $zanahoria = productoParaFoto('Zanahoria');
        $csv = csvImagenes('Zanahoria,zanahoria.webp,Foto: X (CC0)', 'Producto inexistente,otro.webp,Foto: Y (CC0)');

        $this->artisan('catalogo:imagenes', ['archivo' => $csv])->assertSuccessful();
        $this->artisan('catalogo:imagenes', ['archivo' => $csv])->assertSuccessful();

        expect(fotoDe($zanahoria)->imagen_url)->toBe('/productos/zanahoria.webp')
            ->and(DB::table('productos')->where('imagen_url', 'like', '/productos/%')->count())->toBe(1);
    });

    it('rechaza nombres de archivo que no sean una foto webp de la carpeta de productos', function () {
        $papa = productoParaFoto('Papa');
        $csv = csvImagenes('Papa,../index.html,Foto: X (CC0)', 'Papa,https://evil.example/a.webp,Foto: X (CC0)', 'Papa,Papa.WEBP,Foto: X (CC0)');

        $this->artisan('catalogo:imagenes', ['archivo' => $csv])->assertFailed();

        expect(fotoDe($papa)->imagen_url)->toBeNull();
    });

    it('el archivo versionado del repositorio solo apunta a fotos que existen en la PWA', function () {
        $csv = base_path('database/data/imagenes/imagenes-productos.csv');
        expect(is_file($csv))->toBeTrue();

        $filas = array_map('str_getcsv', array_slice(file($csv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), 1));
        expect($filas)->not->toBeEmpty();
        foreach ($filas as [$producto, $archivo, $credito]) {
            expect($archivo)->toMatch('/^[a-z0-9-]+\.webp$/')
                ->and($credito)->not->toBe('')
                ->and(mb_strlen($credito))->toBeLessThanOrEqual(160);
        }
    });
});
