<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Domain\Repositories;

interface EvaluacionesRepositorio
{
    public function guardar(string $observacionId, string $reportadoPor, string $estado, int $confirmaciones, string $permiso): void;

    /**
     * Marca como confirmadas las observaciones corroboradas (alimenta la reputación, RN-07).
     *
     * @param list<string> $observacionIds
     */
    public function marcarConfirmadas(array $observacionIds): void;

    public function aportesConfirmadosDe(string $reportadoPor): int;
}
