<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\ValueObjects;

enum EstadoConfianza
{
    case CONFIABLE;
    case NO_CORROBORADO;
    case POR_VERIFICAR;
    case NO_CONFIABLE;
}
