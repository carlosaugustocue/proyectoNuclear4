<?php

declare(strict_types=1);

namespace App\Domains\Precios\Application\Commands;

final class RegistrarPrecioCommand
{
    public function __construct(
        public readonly string $productoId,
        public readonly string $establecimientoId,
        public readonly string $monto,
        public readonly string $moneda,
        public readonly float  $latitud,
        public readonly float  $longitud,
        public readonly string $reportadoPor,
    ) {}
}
