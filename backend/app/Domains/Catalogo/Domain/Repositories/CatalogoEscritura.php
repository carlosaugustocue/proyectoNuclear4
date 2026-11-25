<?php

declare(strict_types=1);

namespace App\Domains\Catalogo\Domain\Repositories;

/**
 * Puerto de escritura del catálogo para las fuentes de datos iniciales (Ingesta, RF-26, ADR-0010).
 * Solo crea productos GENÉRICOS (sin marca): los de marca entran por el flujo normal del catálogo.
 */
interface CatalogoEscritura
{
    /**
     * Devuelve el id del producto genérico con ese nombre (comparado sin mayúsculas, tildes ni espacios
     * sobrantes, para no duplicar el catálogo: D-04) o lo crea con una presentación de 1 unidad base.
     *
     * @param  'PERECEDERO'|'ABARROTE' $tipoVigencia
     * @param  'KG'|'L'|'UNIDAD'       $unidadBase
     * @return array{id: string, creado: bool}
     */
    public function asegurarProductoGenerico(string $nombre, string $categoria, string $tipoVigencia, string $unidadBase): array;
}
