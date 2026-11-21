<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\ValueObjects;

/**
 * Una observación de precio con todo lo necesario para reevaluarla (p. ej., tras una decisión
 * de moderación). Interfaz pública de Precios; el seudónimo del reportante es solo de uso interno.
 */
final class DetalleObservacion
{
    public function __construct(
        public readonly string $id,
        public readonly string $productoId,
        public readonly string $presentacionId,
        public readonly string $establecimientoId,
        public readonly string $monto,
        public readonly string $observadoEn,
        public readonly string $fuente,
        public readonly string $reportadoPor,
    ) {}
}
