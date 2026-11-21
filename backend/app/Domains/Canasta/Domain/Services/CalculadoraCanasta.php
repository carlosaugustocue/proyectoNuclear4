<?php

declare(strict_types=1);

namespace App\Domains\Canasta\Domain\Services;

use App\Domains\Canasta\Domain\ValueObjects\ItemCanasta;
use App\Domains\Canasta\Domain\ValueObjects\LineaCanasta;
use App\Domains\Canasta\Domain\ValueObjects\OfertaProducto;
use App\Domains\Canasta\Domain\ValueObjects\ResultadoEstablecimiento;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * RN-12 (ADR-0019): por establecimiento, subtotal = menor precio por unidad base vigente × cantidad
 * normalizada; el total suma solo los productos disponibles y los demás se listan como faltantes,
 * nunca como cero. Aritmética decimal exacta (RN-02).
 */
final class CalculadoraCanasta
{
    /**
     * @param  list<ItemCanasta>    $items
     * @param  list<OfertaProducto> $ofertas solo precios vigentes
     * @return list<ResultadoEstablecimiento>
     */
    public static function calcular(array $items, array $ofertas): array
    {
        $resultados = [];
        foreach (self::mejorOfertaPorEstablecimiento($items, $ofertas) as $establecimientoId => $porProducto) {
            $resultados[] = self::resultado((string) $establecimientoId, $items, $porProducto);
        }

        return $resultados;
    }

    /**
     * @param  list<ItemCanasta>    $items
     * @param  list<OfertaProducto> $ofertas
     * @return array<string, array<string, OfertaProducto>> establecimiento => producto => oferta más barata
     */
    private static function mejorOfertaPorEstablecimiento(array $items, array $ofertas): array
    {
        $enCanasta = array_flip(array_map(static fn (ItemCanasta $i): string => $i->productoId, $items));
        $mejor = [];
        foreach ($ofertas as $oferta) {
            if (! isset($enCanasta[$oferta->productoId])) {
                continue;
            }
            $actual = $mejor[$oferta->establecimientoId][$oferta->productoId] ?? null;
            if ($actual === null || BigDecimal::of($oferta->precioPorUnidad)->isLessThan($actual->precioPorUnidad)) {
                $mejor[$oferta->establecimientoId][$oferta->productoId] = $oferta;
            }
        }

        return $mejor;
    }

    /**
     * @param list<ItemCanasta>             $items
     * @param array<string, OfertaProducto> $porProducto
     */
    private static function resultado(string $establecimientoId, array $items, array $porProducto): ResultadoEstablecimiento
    {
        $lineas = [];
        $faltantes = [];
        $total = BigDecimal::zero();
        $nombre = '';

        foreach ($items as $item) {
            $oferta = $porProducto[$item->productoId] ?? null;
            if ($oferta === null) {
                $faltantes[] = $item->productoId;
                continue;
            }
            $subtotal = BigDecimal::of($oferta->precioPorUnidad)
                ->multipliedBy($item->cantidad)
                ->toScale(2, RoundingMode::HALF_UP);
            $total = $total->plus($subtotal);
            $nombre = $oferta->establecimientoNombre;
            $lineas[] = new LineaCanasta($item->productoId, $oferta->precioPorUnidad, $item->cantidad, (string) $subtotal);
        }

        return new ResultadoEstablecimiento(
            $establecimientoId,
            $nombre,
            (string) $total->toScale(2),
            $lineas,
            $faltantes,
            count($items),
        );
    }
}
