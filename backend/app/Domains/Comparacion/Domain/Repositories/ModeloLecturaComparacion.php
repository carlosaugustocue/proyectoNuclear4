<?php

declare(strict_types=1);

namespace App\Domains\Comparacion\Domain\Repositories;

use App\Domains\Comparacion\Domain\ValueObjects\FilaComparacion;
use App\Domains\Compartido\Domain\ValueObjects\Coordenadas;

interface ModeloLecturaComparacion
{
    public function obtener(string $presentacionId, string $establecimientoId): ?FilaComparacion;

    /** Inserta o reemplaza la fila (presentación, establecimiento). */
    public function guardar(FilaComparacion $fila, Coordenadas $ubicacionEstablecimiento): void;

    /**
     * @param  list<string> $productoIds
     * @return list<FilaComparacion>
     */
    public function deProductos(
        array $productoIds,
        ?string $presentacionId = null,
        ?string $municipio = null,
        ?Coordenadas $cerca = null,
        ?float $radioKm = null,
    ): array;
}
