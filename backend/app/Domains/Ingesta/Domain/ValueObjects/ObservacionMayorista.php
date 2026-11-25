<?php

declare(strict_types=1);

namespace App\Domains\Ingesta\Domain\ValueObjects;

use DateTimeImmutable;

/**
 * Precio mayorista promedio de un producto en un mercado y un mes, ya traducido al modelo de la plataforma.
 * Es una REFERENCIA (ADR-0010): no es una observación de góndola ni entra en la comparación.
 */
final class ObservacionMayorista
{
    /**
     * @param 'PERECEDERO'|'ABARROTE' $tipoVigencia
     * @param 'KG'|'L'|'UNIDAD'       $unidadBase
     */
    public function __construct(
        public readonly string $producto,
        public readonly string $codigoCpc,
        public readonly string $categoria,
        public readonly string $tipoVigencia,
        public readonly string $unidadBase,
        public readonly string $mercado,
        public readonly DateTimeImmutable $mes,
        public readonly string $precio,
    ) {}
}
