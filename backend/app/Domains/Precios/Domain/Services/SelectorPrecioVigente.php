<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Services;

use App\Domains\Precios\Domain\ValueObjects\CandidatoPrecio;
use App\Domains\Precios\Domain\ValueObjects\EstadoConfianza;
use App\Domains\Precios\Domain\ValueObjects\EstadoVigencia;
use App\Domains\Precios\Domain\ValueObjects\ResultadoSelector;

final class SelectorPrecioVigente
{
    public static function seleccionar(CandidatoPrecio $candidato): ResultadoSelector
    {
        // Precio vencido nunca se muestra como vigente
        if ($candidato->vigencia === EstadoVigencia::HISTORIAL) {
            return ResultadoSelector::NO_MOSTRAR;
        }

        // Precio no confiable nunca se muestra como vigente
        if ($candidato->confianza === EstadoConfianza::NO_CONFIABLE) {
            return ResultadoSelector::NO_MOSTRAR;
        }

        // Puede estar desactualizado → siempre con advertencia
        if ($candidato->vigencia === EstadoVigencia::PUEDE_ESTAR_DESACTUALIZADO) {
            return ResultadoSelector::MOSTRAR_CON_ADVERTENCIA;
        }

        // Precio en verificación → con advertencia
        if ($candidato->confianza === EstadoConfianza::POR_VERIFICAR) {
            return ResultadoSelector::MOSTRAR_CON_ADVERTENCIA;
        }

        return ResultadoSelector::MOSTRAR;
    }
}
