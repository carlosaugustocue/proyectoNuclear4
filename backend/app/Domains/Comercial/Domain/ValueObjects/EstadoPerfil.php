<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Domain\ValueObjects;

/** Ciclo de vida del perfil comercial (ADR-0022). */
enum EstadoPerfil: string
{
    case PENDIENTE  = 'PENDIENTE';
    case VERIFICADO = 'VERIFICADO';
    case REVOCADO   = 'REVOCADO';
}
