<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Repositories;

use App\Domains\Precios\Domain\Entities\RegistroPrecio;
use DateTimeImmutable;

interface RegistroPrecioRepository
{
    public function save(RegistroPrecio $registro): void;

    /** Id de la observación ya registrada con esa clave (idempotencia, EC-09). */
    public function idPorClaveIdempotencia(string $clave): ?string;

    /** Reportes del reportante desde el instante dado, incluidos los reemplazados (RN-08). */
    public function contarAportesDesde(string $reportadoPor, DateTimeImmutable $desde): int;

    public function existeAporteDelDia(
        string $reportadoPor,
        string $presentacionId,
        string $establecimientoId,
        DateTimeImmutable $inicioDia,
        DateTimeImmutable $finDia,
    ): bool;

    /** Retira (historial) los reportes activos del mismo reportante para la presentación, el establecimiento y el día. */
    public function retirarAportesDelDia(
        string $reportadoPor,
        string $presentacionId,
        string $establecimientoId,
        DateTimeImmutable $inicioDia,
        DateTimeImmutable $finDia,
    ): int;
}
