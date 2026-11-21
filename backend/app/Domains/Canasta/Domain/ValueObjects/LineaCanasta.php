<?php

declare(strict_types=1);

namespace App\Domains\Canasta\Domain\ValueObjects;

final class LineaCanasta
{
    public function __construct(
        public readonly string $productoId,
        public readonly string $precioPorUnidad,
        public readonly string $cantidad,
        public readonly string $subtotal,
    ) {}
}
