<?php

declare(strict_types=1);

namespace App\Domains\Canasta\Domain\ValueObjects;

/** Precio vigente de un producto en un establecimiento, ya normalizado por unidad base (RN-01, RN-04, RN-09). */
final class OfertaProducto
{
    public function __construct(
        public readonly string $productoId,
        public readonly string $establecimientoId,
        public readonly string $establecimientoNombre,
        public readonly string $precioPorUnidad,
    ) {}
}
