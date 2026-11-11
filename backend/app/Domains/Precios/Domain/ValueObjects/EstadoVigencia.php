<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\ValueObjects;

enum EstadoVigencia
{
    case VIGENTE;
    case PUEDE_ESTAR_DESACTUALIZADO;
    case HISTORIAL;
}
