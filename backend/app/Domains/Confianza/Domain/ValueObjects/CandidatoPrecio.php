<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Domain\ValueObjects;

final class CandidatoPrecio
{
    public function __construct(
        public readonly string         $monto,
        public readonly EstadoConfianza $confianza,
        public readonly EstadoVigencia  $vigencia,
    ) {}
}
