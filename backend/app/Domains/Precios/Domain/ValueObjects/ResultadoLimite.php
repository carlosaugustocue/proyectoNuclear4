<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\ValueObjects;

enum ResultadoLimite
{
    case PERMITIDO;
    case REEMPLAZAR;
    case LIMITE_EXCEDIDO;
}
