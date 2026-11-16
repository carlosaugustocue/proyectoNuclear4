<?php

declare(strict_types=1);

namespace App\Domains\Precios\Application\Commands;

use App\Domains\Precios\Domain\ValueObjects\FuenteObservacion;
use DateTimeImmutable;

final class RegistrarReportePrecioCommand
{
    public function __construct(
        public readonly string            $presentacionId,
        public readonly string            $establecimientoId,
        public readonly string            $precio,
        public readonly DateTimeImmutable $observadoEn,
        public readonly string            $reportadoPor,
        public readonly string            $claveIdempotencia,
        public readonly FuenteObservacion $fuente = FuenteObservacion::REPORTANTE,
    ) {}
}
