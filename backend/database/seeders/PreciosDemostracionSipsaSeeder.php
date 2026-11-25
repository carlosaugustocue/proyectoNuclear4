<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domains\Precios\Application\Commands\RegistrarReportePrecioCommand;
use App\Domains\Precios\Application\Handlers\RegistrarReportePrecioHandler;
use App\Domains\Precios\Domain\ValueObjects\FuenteObservacion;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * Catálogo real de Armenia (SIPSA-DANE) y precios de góndola DE DEMOSTRACIÓN para esos productos.
 *
 * 1. Corre `ingesta:sipsa`: 115 productos genéricos y su referencia mayorista (RF-25, RF-26, ADR-0010).
 * 2. Para cada producto sin precios en tiendas, siembra reportes en 4 de los establecimientos con un precio
 *    de góndola SUPUESTO = último precio mayorista × margen minorista × factor de la tienda. Son datos de
 *    demostración (fuente EQUIPO_SEMILLA), no precios observados; la referencia mayorista sigue aparte.
 *
 * Idempotente: no vuelve a sembrar un producto que ya tiene precios en la comparación.
 */
final class PreciosDemostracionSipsaSeeder extends Seeder
{
    /** Margen minorista supuesto sobre el precio mayorista (solo para la demostración). */
    private const MARGEN_MINORISTA = 1.35;

    /** Factor de precio por posición del establecimiento: unas tiendas un poco más caras que otras. */
    private const FACTORES = [1.06, 1.02, 0.97, 1.04, 0.99, 0.95, 1.08, 1.00];

    private const TIENDAS_POR_PRODUCTO = 4;

    public function run(): void
    {
        config(['queue.default' => 'sync']); // proyección inmediata, sin worker
        Artisan::call('ingesta:sipsa');

        $establecimientos = DB::table('establecimientos')->orderBy('created_at')->orderBy('id')->pluck('id')->all();
        if ($establecimientos === []) {
            return;
        }

        $productos = DB::table('referencias_mayoristas as r')
            ->join('presentaciones as p', 'p.producto_id', '=', 'r.producto_id')
            ->whereColumn('p.unidad_base', 'r.unidad_base')
            ->where('p.cantidad', 1)
            ->whereNotExists(static fn ($q) => $q->from('comparacion_vigente as c')->whereColumn('c.producto_id', 'r.producto_id'))
            ->selectRaw('r.producto_id, min(p.id::text) AS presentacion_id, (array_agg(r.precio ORDER BY r.mes DESC))[1] AS precio')
            ->groupBy('r.producto_id')
            ->orderBy('r.producto_id')
            ->get();

        $handler = app(RegistrarReportePrecioHandler::class);
        foreach ($productos as $i => $producto) {
            for ($k = 0; $k < self::TIENDAS_POR_PRODUCTO; $k++) {
                $posicion = ($i * 3 + $k * 2) % count($establecimientos);
                $precio = self::redondear((float) $producto->precio * self::MARGEN_MINORISTA * self::FACTORES[$posicion % count(self::FACTORES)]);
                $horas = ($i * 5 + $k * 11) % 60; // entre 0 y 2,5 días: vigentes también para perecederos

                foreach ([0, 1] as $reportante) {
                    if ($reportante === 1 && ($i + $k) % 3 !== 0) {
                        continue; // una de cada tres observaciones queda corroborada
                    }
                    $handler->handle(new RegistrarReportePrecioCommand(
                        presentacionId:    (string) $producto->presentacion_id,
                        establecimientoId: (string) $establecimientos[$posicion],
                        precio:            (string) ($precio + $reportante * 50),
                        observadoEn:       new DateTimeImmutable("-{$horas} hours"),
                        reportadoPor:      "semilla-sipsa-{$i}-{$k}-{$reportante}", // uno por reporte: no choca con RN-08
                        claveIdempotencia: Uuid::uuid4()->toString(),
                        fuente:            FuenteObservacion::EQUIPO_SEMILLA,
                    ));
                }
            }
        }
    }

    /** A múltiplos de $50 (de $10 por debajo de $1.000, para huevos y similares). */
    private static function redondear(float $precio): int
    {
        $paso = $precio < 1000 ? 10 : 50;

        return (int) (round($precio / $paso) * $paso);
    }
}
