<?php

declare(strict_types=1);

namespace App\Domains\Identidad\Domain\ValueObjects;

enum EstadoCuenta: string
{
    case ACTIVA     = 'ACTIVA';
    case SUSPENDIDA = 'SUSPENDIDA';
}
