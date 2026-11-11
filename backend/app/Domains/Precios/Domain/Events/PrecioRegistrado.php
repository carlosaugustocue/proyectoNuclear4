<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Events;

use DateTimeImmutable;

final class PrecioRegistrado
{
    public readonly DateTimeImmutable $occurredOn;

    public function __construct(
        public readonly string $registroId,
        public readonly string $productoId,
        public readonly string $establecimientoId,
        public readonly string $monto,
        public readonly string $moneda,
        public readonly float  $latitud,
        public readonly float  $longitud,
        public readonly string $reportadoPor,
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }
}
