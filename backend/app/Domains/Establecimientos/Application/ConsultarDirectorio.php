<?php

declare(strict_types=1);

namespace App\Domains\Establecimientos\Application;

use App\Domains\Compartido\Domain\ValueObjects\Coordenadas;
use App\Domains\Establecimientos\Domain\Repositories\DirectorioLectura;
use App\Domains\Establecimientos\Domain\ValueObjects\EstablecimientoPublico;

/** Interfaz pública del contexto Establecimientos (RF-05, RF-06). */
final class ConsultarDirectorio
{
    public const RADIO_POR_DEFECTO_KM = 3.0;

    public function __construct(private readonly DirectorioLectura $lectura) {}

    /** @return array{items: list<EstablecimientoPublico>, total: int} */
    public function buscar(
        ?string $texto,
        ?string $municipio,
        ?Coordenadas $cerca,
        ?float $radioKm,
        int $pagina,
        int $tamano,
    ): array {
        return $this->lectura->buscar($texto, $municipio, $cerca, $radioKm ?? self::RADIO_POR_DEFECTO_KM, $pagina, $tamano);
    }

    public function porId(string $id): ?EstablecimientoPublico
    {
        return $this->lectura->porId($id);
    }

    /**
     * @param  list<string> $ids
     * @return array<string, float>
     */
    public function distanciasKm(Coordenadas $desde, array $ids): array
    {
        return $ids === [] ? [] : $this->lectura->distanciasKm($desde, $ids);
    }
}
