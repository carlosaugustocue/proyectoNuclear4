<?php

declare(strict_types=1);

namespace App\Domains\Canasta\Domain\ValueObjects;

/** Ítem validado: cantidad expresada en la unidad base del producto (kg, L o unidad). */
final class ItemCanasta
{
    public function __construct(
        public readonly string $productoId,
        public readonly string $cantidad,
        public readonly string $unidadBase,
    ) {}
}
