<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Services;

use App\Domains\Precios\Domain\ValueObjects\ItemComparacion;

final class OrdenComparacion
{
    /**
     * Ordena por precio/unidad base ASC.
     * En empate: más reciente primero.
     * Los atributos comerciales se ignoran completamente (AR-02 neutralidad).
     *
     * @param  list<ItemComparacion> $items
     * @return list<ItemComparacion>
     */
    public static function porPrecioUnitario(array $items): array
    {
        usort($items, function (ItemComparacion $a, ItemComparacion $b): int {
            $precioA = $a->precioPorUnidadBase();
            $precioB = $b->precioPorUnidadBase();

            if ($precioA !== $precioB) {
                return $precioA <=> $precioB;
            }

            // Empate: más reciente primero
            return $b->registradoEn <=> $a->registradoEn;
        });

        return $items;
    }

    /**
     * Ordena por distancia ASC (el más cercano primero).
     *
     * @param  list<ItemComparacion> $items
     * @return list<ItemComparacion>
     */
    public static function porDistancia(array $items): array
    {
        usort($items, fn (ItemComparacion $a, ItemComparacion $b): int =>
            ($a->distanciaMetros ?? PHP_INT_MAX) <=> ($b->distanciaMetros ?? PHP_INT_MAX)
        );

        return $items;
    }
}
