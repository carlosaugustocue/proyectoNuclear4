<?php

declare(strict_types=1);

namespace App\Domains\Establecimientos\Domain\Repositories;

use App\Domains\Compartido\Domain\ValueObjects\Coordenadas;
use App\Domains\Establecimientos\Domain\ValueObjects\EstablecimientoPublico;

interface DirectorioLectura
{
    /** @return array{items: list<EstablecimientoPublico>, total: int} */
    public function buscar(
        ?string $texto,
        ?string $municipio,
        ?Coordenadas $cerca,
        float $radioKm,
        int $pagina,
        int $tamano,
    ): array;

    public function porId(string $id): ?EstablecimientoPublico;

    /**
     * Distancia en km desde un punto a cada establecimiento indicado.
     *
     * @param  list<string> $ids
     * @return array<string, float>
     */
    public function distanciasKm(Coordenadas $desde, array $ids): array;
}
