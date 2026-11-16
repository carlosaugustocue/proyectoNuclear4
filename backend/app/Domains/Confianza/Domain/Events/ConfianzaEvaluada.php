<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Domain\Events;

/**
 * Resultado de evaluar una observación (ADR-0007). Interfaz pública de Confianza:
 * la consumen Comparación (proyección) y Moderación (casos por anomalía).
 * No contiene la identidad del reportante.
 */
final class ConfianzaEvaluada
{
    public function __construct(
        public readonly string $observacionId,
        public readonly string $productoId,
        public readonly string $presentacionId,
        public readonly string $establecimientoId,
        public readonly string $monto,
        public readonly string $observadoEn,
        public readonly string $fuente,
        public readonly string $estado,          // EstadoConfianza::name
        public readonly int    $confirmaciones,
        public readonly string $permiso,         // PermisoAporte::name
    ) {}
}
