<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Domain\ValueObjects;

use DateTimeImmutable;

/** Precio que hoy publica la comparación para una presentación en un establecimiento. */
final class ObservacionVigente
{
    public function __construct(
        public readonly string            $monto,
        public readonly EstadoConfianza   $estado,
        public readonly EstadoVigencia    $vigencia,
        public readonly DateTimeImmutable $observadoEn,
    ) {}
}
