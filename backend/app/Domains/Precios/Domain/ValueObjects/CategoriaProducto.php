<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\ValueObjects;

enum CategoriaProducto
{
    case PERECEDERO;   // vigencia 3 días (RN-04)
    case ABARROTE;     // vigencia 7 días (RN-04)
}
