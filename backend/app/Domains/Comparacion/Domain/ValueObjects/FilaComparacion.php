<?php

declare(strict_types=1);

namespace App\Domains\Comparacion\Domain\ValueObjects;

use DateTimeImmutable;

/**
 * Fila del modelo de lectura `comparacion_vigente` (CQRS ligero, ADR-0003): una por
 * presentación y establecimiento, desnormalizada para responder sin JOINs (EC-04).
 */
final class FilaComparacion
{
    /**
     * @param array{id: string, nombre: string, tipo: string, barrio: ?string, municipio: string} $establecimiento
     * @param array{id: string, descripcion: ?string, cantidad: string, unidad: string, unidadesPorEmpaque: int, unidadBase: string, ean: ?string} $presentacion
     */
    public function __construct(
        public readonly string            $productoId,
        public readonly string            $observacionId,
        public readonly string            $precio,
        public readonly string            $precioPorUnidad,
        public readonly string            $unidadBase,
        public readonly DateTimeImmutable $observadoEn,
        public readonly string            $fuente,
        public readonly int               $confirmaciones,
        public readonly string            $estadoConfianza,
        public readonly string            $tipoVigencia,
        public readonly array             $establecimiento,
        public readonly array             $presentacion,
        public readonly ?float            $distanciaKm = null,
    ) {}
}
