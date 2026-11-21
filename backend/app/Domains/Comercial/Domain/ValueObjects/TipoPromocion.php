<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Domain\ValueObjects;

enum TipoPromocion: string
{
    case DESCUENTO_PORCENTUAL = 'DESCUENTO_PORCENTUAL';
    case PRECIO_ESPECIAL      = 'PRECIO_ESPECIAL';
    case PAGUE_X_LLEVE_Y      = 'PAGUE_X_LLEVE_Y';
}
