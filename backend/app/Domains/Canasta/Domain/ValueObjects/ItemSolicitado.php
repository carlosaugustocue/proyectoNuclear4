<?php

declare(strict_types=1);

namespace App\Domains\Canasta\Domain\ValueObjects;

/** Ítem tal como lo envía el consumidor: cantidad en cualquier unidad conocida (g, kg, mL, L, unidad). */
final class ItemSolicitado
{
    public function __construct(
        public readonly string $productoId,
        public readonly string $cantidad,
        public readonly string $unidad,
    ) {}
}
