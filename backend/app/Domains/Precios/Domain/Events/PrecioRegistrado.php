<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Events;

use DateTimeImmutable;

/**
 * Evento de dominio publicado vía outbox (ADR-0003). Es parte de la interfaz pública
 * del contexto Precios: lo consumen Confianza (evaluación) y, a través de ella,
 * Comparación (proyección del modelo de lectura).
 *
 * `reportadoPor` es un seudónimo interno; nunca se expone en respuestas públicas (EC-07).
 */
final class PrecioRegistrado
{
    public readonly DateTimeImmutable $occurredOn;

    public function __construct(
        public readonly string $registroId,
        public readonly string $productoId,
        public readonly string $presentacionId,
        public readonly string $establecimientoId,
        public readonly string $monto,
        public readonly string $moneda,
        public readonly string $observadoEn,
        public readonly string $reportadoPor,
        public readonly string $fuente,
    ) {
        $this->occurredOn = new DateTimeImmutable();
    }
}
