<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Domain\Services;

use App\Domains\Confianza\Domain\ValueObjects\EstadoConfianza;

/**
 * Estado publicado de una observación (ADR-0007): combina el resultado del detector de
 * anomalías (RN-06) con el de la corroboración (RN-05). La anomalía prevalece para que
 * un grupo coordinado no pueda "corroborar" un precio atípico (EC-03).
 */
final class EvaluadorConfianza
{
    public static function combinar(EstadoConfianza $anomalia, EstadoConfianza $corroboracion): EstadoConfianza
    {
        if ($anomalia === EstadoConfianza::POR_VERIFICAR) {
            return EstadoConfianza::POR_VERIFICAR;
        }

        return $corroboracion === EstadoConfianza::CONFIABLE
            ? EstadoConfianza::CONFIABLE
            : EstadoConfianza::NO_CORROBORADO;
    }
}
