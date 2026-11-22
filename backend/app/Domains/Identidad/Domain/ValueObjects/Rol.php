<?php

declare(strict_types=1);

namespace App\Domains\Identidad\Domain\ValueObjects;

enum Rol: string
{
    case CIUDADANO = 'CIUDADANO';
    case MODERADOR = 'MODERADOR';
}
