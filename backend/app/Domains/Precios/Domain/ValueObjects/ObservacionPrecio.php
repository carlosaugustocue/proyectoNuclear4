<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\ValueObjects;

use DateTimeImmutable;

/** Lectura de una observación registrada (interfaz pública de Precios para Confianza). */
final class ObservacionPrecio
{
    public function __construct(
        public readonly string            $id,
        public readonly string            $reportadoPor,
        public readonly string            $monto,
        public readonly DateTimeImmutable $observadoEn,
    ) {}
}
