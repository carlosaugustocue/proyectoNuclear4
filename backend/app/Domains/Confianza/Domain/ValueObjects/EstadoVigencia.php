<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Domain\ValueObjects;

enum EstadoVigencia
{
    case VIGENTE;
    case PUEDE_ESTAR_DESACTUALIZADO;
    case HISTORIAL;
}
