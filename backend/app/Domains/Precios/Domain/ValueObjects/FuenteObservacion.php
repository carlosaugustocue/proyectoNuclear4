<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\ValueObjects;

/** Origen de una observación (contrato: FuenteObservacion). Nunca identifica al reportante. */
enum FuenteObservacion: string
{
    case REPORTANTE     = 'REPORTANTE';
    case COMERCIO       = 'COMERCIO';        // corte 3 (módulo Comercial)
    case EQUIPO_SEMILLA = 'EQUIPO_SEMILLA';  // datos iniciales (ADR-0010)
}
