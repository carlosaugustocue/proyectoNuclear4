<?php

declare(strict_types=1);

namespace App\Domains\Compartido\Domain\ValueObjects;

final class PrecioUnitario
{
    public function __construct(
        public readonly string $precioPorUnidad, // decimal exacto con 2 cifras (contrato Dinero)
        public readonly string $unidadBase,      // KG | L | UNIDAD
    ) {}
}
