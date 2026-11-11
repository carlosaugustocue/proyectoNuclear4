<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\ValueObjects;

enum ResultadoSelector
{
    case MOSTRAR;
    case MOSTRAR_CON_ADVERTENCIA;
    case NO_MOSTRAR;
}
