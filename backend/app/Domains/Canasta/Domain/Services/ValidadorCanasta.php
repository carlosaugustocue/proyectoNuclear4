<?php

declare(strict_types=1);

namespace App\Domains\Canasta\Domain\Services;

use App\Domains\Canasta\Domain\Exceptions\CanastaInvalidaException;
use App\Domains\Canasta\Domain\ValueObjects\ItemCanasta;
use App\Domains\Canasta\Domain\ValueObjects\ItemSolicitado;
use App\Domains\Compartido\Domain\Services\Comparabilidad;
use App\Domains\Compartido\Domain\Services\NormalizadorUnidad;
use Brick\Math\BigDecimal;
use InvalidArgumentException;

/** RN-11 (ADR-0018): de 1 a 20 productos distintos, cantidad > 0 y unidad comparable con la del producto. */
final class ValidadorCanasta
{
    public const MAX_PRODUCTOS = 20;

    /**
     * @param  list<ItemSolicitado>  $items
     * @param  array<string, string> $unidadBasePorProducto productoId => unidad base (kg, L, unidad)
     * @return list<ItemCanasta>
     *
     * @throws CanastaInvalidaException con todos los errores encontrados
     */
    public static function validar(array $items, array $unidadBasePorProducto): array
    {
        $errores = [];
        $total = count($items);
        if ($total === 0 || $total > self::MAX_PRODUCTOS) {
            $errores[] = sprintf('La canasta debe tener entre 1 y %d productos; tiene %d.', self::MAX_PRODUCTOS, $total);
        }

        $vistos = [];
        $validos = [];
        foreach ($items as $item) {
            if (isset($vistos[$item->productoId])) {
                $errores[] = "El producto {$item->productoId} está repetido.";
                continue;
            }
            $vistos[$item->productoId] = true;

            $validado = self::validarItem($item, $unidadBasePorProducto[$item->productoId] ?? null, $errores);
            if ($validado !== null) {
                $validos[] = $validado;
            }
        }

        if ($errores !== []) {
            throw new CanastaInvalidaException($errores);
        }

        return $validos;
    }

    /** @param list<string> $errores */
    private static function validarItem(ItemSolicitado $item, ?string $unidadBase, array &$errores): ?ItemCanasta
    {
        if ($unidadBase === null) {
            $errores[] = "El producto {$item->productoId} no existe en el catálogo.";

            return null;
        }
        if (! is_numeric($item->cantidad) || BigDecimal::of($item->cantidad)->isLessThanOrEqualTo(0)) {
            $errores[] = "La cantidad de {$item->productoId} debe ser mayor que cero.";

            return null;
        }

        try {
            $factor = NormalizadorUnidad::normalizar('1', $item->unidad);
        } catch (InvalidArgumentException) {
            $errores[] = "La unidad «{$item->unidad}» de {$item->productoId} es desconocida.";

            return null;
        }

        if (! Comparabilidad::sonComparables($factor->unidadBase, $unidadBase)) {
            $errores[] = "La unidad «{$item->unidad}» no es comparable con la unidad base «{$unidadBase}» de {$item->productoId}.";

            return null;
        }

        $cantidad = BigDecimal::of($item->cantidad)->multipliedBy((string) $factor->cantidad)->stripTrailingZeros();

        return new ItemCanasta($item->productoId, (string) $cantidad, $factor->unidadBase);
    }
}
