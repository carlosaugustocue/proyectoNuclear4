<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Precios\Application\Commands\RegistrarReportePrecioCommand;
use App\Domains\Precios\Application\Handlers\RegistrarReportePrecioHandler;
use App\Domains\Precios\Domain\ValueObjects\FuenteObservacion;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * Datos de DEMOSTRACIÓN del equipo (ADR-0010, fuente EQUIPO_SEMILLA).
 *
 * ⚠ Los establecimientos son FICTICIOS y los precios son ILUSTRATIVOS: sirven para
 * mostrar el funcionamiento de la plataforma en la demo y en la prueba de usabilidad,
 * no son observaciones reales de campo ni deben citarse como evidencia (Ley 1480/2011).
 *
 * Los precios entran por el caso de uso real (RegistrarReportePrecioHandler), así que
 * recorren el mismo flujo que un reporte: confianza → proyección → comparación.
 */
final class SemillaDemostracionSeeder extends Seeder
{
    /** [nombre, marca, categoría, tipo_vigencia, alias, [[descripción, cantidad, unidad, empaque, base, precio base]]] */
    private const PRODUCTOS = [
        ['Arroz blanco', 'Diana', 'Granos', 'ABARROTE', ['arroz'], [['Bolsa 500 g', '500', 'G', 1, 'KG', 2650], ['Bolsa 1 kg', '1', 'KG', 1, 'KG', 4990]]],
        ['Lenteja', 'La Cosecha', 'Granos', 'ABARROTE', ['lentejas'], [['Bolsa 500 g', '500', 'G', 1, 'KG', 3900]]],
        ['Panela', 'El Trapiche', 'Granos', 'ABARROTE', ['dulce de caña'], [['Bloque 500 g', '500', 'G', 1, 'KG', 2400]]],
        ['Leche entera UHT', 'Colanta', 'Lácteos', 'PERECEDERO', ['leche'], [['Bolsa 1 L', '1', 'L', 1, 'L', 3600], ['Six pack 6 x 1 L', '1', 'L', 6, 'L', 20500]]],
        ['Huevos AA', 'Granja Quindío', 'Lácteos', 'PERECEDERO', ['huevos rojos'], [['Cubeta x 30', '30', 'UNIDAD', 1, 'UNIDAD', 16500]]],
        ['Aceite de girasol', 'Premier', 'Aceites', 'ABARROTE', ['aceite'], [['Botella 1 L', '1', 'L', 1, 'L', 11900], ['Botella 500 mL', '500', 'ML', 1, 'L', 6400]]],
        ['Café molido', 'Del Quindío', 'Bebidas', 'ABARROTE', ['café'], [['Bolsa 250 g', '250', 'G', 1, 'KG', 9800]]],
        ['Gaseosa cola', 'Postobón', 'Bebidas', 'ABARROTE', ['gaseosa'], [['Botella 1.5 L', '1.5', 'L', 1, 'L', 4500], ['Paca 6 x 330 mL', '330', 'ML', 6, 'L', 9900]]],
        ['Tomate chonto', null, 'Frutas y verduras', 'PERECEDERO', ['tomate'], [['Por kilo', '1', 'KG', 1, 'KG', 4200]]],
        ['Plátano hartón', null, 'Frutas y verduras', 'PERECEDERO', ['platano'], [['Por kilo', '1', 'KG', 1, 'KG', 3100]]],
        ['Jabón en barra', 'Rey', 'Aseo', 'ABARROTE', ['jabón de ropa'], [['Paquete x 3', '3', 'UNIDAD', 1, 'UNIDAD', 6300]]],
        ['Papel higiénico', 'Familia', 'Aseo', 'ABARROTE', ['papel'], [['Paquete x 12', '12', 'UNIDAD', 1, 'UNIDAD', 18900]]],
    ];

    /**
     * Fotos con licencia libre, por [nombre, marca] del producto. Solo coincidencias verificadas por
     * código de barras en Open Food Facts (CC BY-SA 3.0); el resto muestra la ilustración de su
     * categoría. Nunca fotos de catálogos de cadenas (R-06). La migración 2026_11_22_000002 las aplica
     * también a bases ya sembradas.
     */
    public const IMAGENES = [
        ['Arroz blanco', 'Diana', 'https://images.openfoodfacts.org/images/products/770/251/100/0014/front_en.30.400.jpg'],
        ['Aceite de girasol', 'Premier', 'https://images.openfoodfacts.org/images/products/770/101/800/7151/front_es.3.400.jpg'],
    ];

    public const CREDITO_OPEN_FOOD_FACTS = 'Foto: Open Food Facts (CC BY-SA 3.0)';

    /** [nombre, tipo, dirección, barrio, lat, lon, factor de precio] — establecimientos ficticios de Armenia */
    private const ESTABLECIMIENTOS = [
        ['Tienda Doña Rosa', 'TIENDA_BARRIO', 'Cra 15 # 20-10', 'Centro', 4.5352, -75.6757, 1.06],
        ['Minimercado La Esquina', 'MINIMERCADO', 'Calle 21 # 18-04', 'Centro', 4.5371, -75.6732, 1.02],
        ['Supermercado El Parque', 'SUPERMERCADO', 'Av. Bolívar # 14N-30', 'Norte', 4.5528, -75.6604, 0.97],
        ['Tienda El Paisa', 'TIENDA_BARRIO', 'Cra 19 # 32-15', 'Granada', 4.5297, -75.6828, 1.04],
        ['Autoservicio La Castellana', 'MINIMERCADO', 'Calle 50 # 23-45', 'La Castellana', 4.5462, -75.6651, 0.99],
        ['Supermercado Los Andes', 'SUPERMERCADO', 'Av. 19 # 8-20', 'Los Andes', 4.5241, -75.6884, 0.95],
        ['Tienda Don Pedro', 'TIENDA_BARRIO', 'Mz 12 Casa 4', 'La Patria', 4.5168, -75.6955, 1.08],
        ['Fruver La Cosecha', 'MINIMERCADO', 'Calle 30 # 16-22', 'Galán', 4.5320, -75.6790, 1.00],
    ];

    public function run(): void
    {
        // Proyección inmediata (sin worker) para que la demo quede lista al terminar el seed.
        config(['queue.default' => 'sync']);

        $presentaciones   = $this->sembrarCatalogo();
        $establecimientos = $this->sembrarEstablecimientos();
        $this->sembrarPrecios($presentaciones, $establecimientos);
    }

    /** @return list<array{id: string, precio: int}> */
    /** Asigna la foto conocida del producto, si la hay. Idempotente. */
    public static function aplicarImagen(string $productoId, string $nombre, ?string $marca): void
    {
        foreach (self::IMAGENES as [$n, $m, $url]) {
            if ($n === $nombre && $m === $marca) {
                DB::table('productos')->where('id', $productoId)
                    ->update(['imagen_url' => $url, 'imagen_credito' => self::CREDITO_OPEN_FOOD_FACTS]);
            }
        }
    }

    private function sembrarCatalogo(): array
    {
        $resultado = [];
        foreach (self::PRODUCTOS as [$nombre, $marca, $categoria, $vigencia, $alias, $presentaciones]) {
            $productoId = Uuid::uuid7()->toString();
            DB::table('productos')->insert([
                'id' => $productoId, 'nombre' => $nombre, 'marca' => $marca, 'categoria' => $categoria,
                'tipo_vigencia' => $vigencia, 'alias' => json_encode($alias, JSON_UNESCAPED_UNICODE),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            self::aplicarImagen($productoId, $nombre, $marca);

            foreach ($presentaciones as [$descripcion, $cantidad, $unidad, $empaque, $base, $precio]) {
                $id = Uuid::uuid7()->toString();
                DB::table('presentaciones')->insert([
                    'id' => $id, 'producto_id' => $productoId, 'descripcion' => $descripcion, 'cantidad' => $cantidad,
                    'unidad' => $unidad, 'unidades_por_empaque' => $empaque, 'unidad_base' => $base,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $resultado[] = ['id' => $id, 'precio' => $precio];
            }
        }

        return $resultado;
    }

    /** @return list<array{id: string, factor: float}> */
    private function sembrarEstablecimientos(): array
    {
        $resultado = [];
        foreach (self::ESTABLECIMIENTOS as [$nombre, $tipo, $direccion, $barrio, $lat, $lon, $factor]) {
            $id = Uuid::uuid7()->toString();
            DB::table('establecimientos')->insert([
                'id' => $id, 'nombre' => $nombre, 'tipo' => $tipo, 'direccion' => $direccion, 'barrio' => $barrio,
                'municipio' => 'Armenia', 'ubicacion' => DB::raw("ST_GeogFromText('POINT({$lon} {$lat})')"),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $resultado[] = ['id' => $id, 'factor' => $factor];
        }

        return $resultado;
    }

    /**
     * Cada presentación se "observa" en 5 de los 8 establecimientos, con dos reportes
     * semilla independientes (para que algunas observaciones queden corroboradas) y
     * edades distintas (para ver vigente / puede estar desactualizado).
     *
     * @param list<array{id: string, precio: int}>   $presentaciones
     * @param list<array{id: string, factor: float}> $establecimientos
     */
    private function sembrarPrecios(array $presentaciones, array $establecimientos): void
    {
        $handler = app(RegistrarReportePrecioHandler::class);
        $total   = count($establecimientos);

        foreach ($presentaciones as $i => $presentacion) {
            for ($k = 0; $k < 5; $k++) {
                $est    = $establecimientos[($i + $k * 3) % $total]; // 5 establecimientos distintos
                $precio = (int) (round($presentacion['precio'] * $est['factor'] / 50) * 50);
                $horas  = ($i * 7 + $k * 13) % 200; // entre 0 y ~8 días

                foreach ([0, 1] as $reportante) {
                    if ($reportante === 1 && $k % 2 === 1) {
                        continue; // solo algunas observaciones quedan corroboradas
                    }
                    $handler->handle(new RegistrarReportePrecioCommand(
                        presentacionId:    $presentacion['id'],
                        establecimientoId: $est['id'],
                        precio:            (string) ($precio + $reportante * 50),
                        observadoEn:       new DateTimeImmutable("-{$horas} hours"),
                        reportadoPor:      "semilla-{$reportante}-{$k}",
                        claveIdempotencia: Uuid::uuid4()->toString(),
                        fuente:            FuenteObservacion::EQUIPO_SEMILLA,
                    ));
                }
            }
        }
    }
}
