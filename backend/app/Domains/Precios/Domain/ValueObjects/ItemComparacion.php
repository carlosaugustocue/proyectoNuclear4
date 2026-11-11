<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\ValueObjects;

use DateTimeImmutable;

final class ItemComparacion
{
    public function __construct(
        public readonly string            $establecimientoId,
        public readonly string            $monto,
        public readonly string            $cantidad,
        public readonly string            $unidad,
        public readonly DateTimeImmutable $registradoEn,
        public readonly ?string           $atributoComercial,
        public readonly ?float            $distanciaMetros = null,
    ) {}

    public function precioPorUnidadBase(): float
    {
        $norm = \App\Domains\Precios\Domain\Services\NormalizadorUnidad::normalizar(
            $this->cantidad,
            $this->unidad
        );
        return (float) $this->monto / $norm->cantidad;
    }
}
