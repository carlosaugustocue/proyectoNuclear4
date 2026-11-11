<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\ValueObjects;

enum PermisoAporte
{
    case PUEDE_ACTUALIZAR_VIGENTE;
    case REQUIERE_CORROBORACION;
    case RECHAZADO;
}
