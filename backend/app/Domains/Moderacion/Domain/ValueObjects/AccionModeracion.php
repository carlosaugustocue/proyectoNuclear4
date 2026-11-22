<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Domain\ValueObjects;

/** Acciones de un moderador sobre un caso (RF-20). */
enum AccionModeracion: string
{
    case CONFIRMAR_OBSERVACION = 'CONFIRMAR_OBSERVACION';
    case INVALIDAR_OBSERVACION = 'INVALIDAR_OBSERVACION';
    case SUSPENDER_CUENTA      = 'SUSPENDER_CUENTA';
    case CERRAR_SIN_CAMBIOS    = 'CERRAR_SIN_CAMBIOS';

    /** ¿La acción cambia el estado de una observación de precio? */
    /** Suspender al autor también invalida la observación que originó el caso. */
    public function actuaSobreObservacion(): bool
    {
        return $this !== self::CERRAR_SIN_CAMBIOS;
    }
}
