<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Repositories;

use App\Domains\Precios\Domain\ValueObjects\DetalleObservacion;
use App\Domains\Precios\Domain\ValueObjects\ObservacionPrecio;
use DateTimeImmutable;

/** Consultas de historial que necesita el contexto Confianza (RN-05, RN-06, RN-07). */
interface ObservacionesLectura
{
    /**
     * Último precio activo de la presentación en cada establecimiento desde la fecha indicada.
     *
     * @return list<string>
     */
    public function ultimoPrecioPorEstablecimiento(string $presentacionId, string $excluirId, DateTimeImmutable $desde): array;

    /**
     * Historial de precios de la presentación en un establecimiento (más recientes primero).
     *
     * @return list<string>
     */
    public function historialEnEstablecimiento(string $presentacionId, string $establecimientoId, string $excluirId, int $limite): array;

    /**
     * Observaciones activas de la presentación en el establecimiento desde la fecha indicada.
     *
     * @return list<ObservacionPrecio>
     */
    public function recientesEnEstablecimiento(string $presentacionId, string $establecimientoId, DateTimeImmutable $desde): array;

    public function primerAporteDe(string $reportadoPor): ?DateTimeImmutable;

    /** Observación completa por id; null si no existe o fue retirada. */
    public function detalle(string $observacionId): ?DetalleObservacion;
}
