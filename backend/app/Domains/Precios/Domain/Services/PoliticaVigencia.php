<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Services;

use App\Domains\Precios\Domain\ValueObjects\CategoriaProducto;
use App\Domains\Precios\Domain\ValueObjects\EstadoVigencia;
use DateTimeImmutable;

final class PoliticaVigencia
{
    /** Días máximos de vigencia por categoría (RN-04) */
    private const PLAZO_DIAS = [
        CategoriaProducto::PERECEDERO->name => 3,
        CategoriaProducto::ABARROTE->name   => 7,
    ];

    public static function evaluar(
        DateTimeImmutable $registradoEn,
        CategoriaProducto $categoria,
    ): EstadoVigencia {
        $plazo     = self::PLAZO_DIAS[$categoria->name];
        $diasTrans = (new DateTimeImmutable())->diff($registradoEn)->days;

        if ($diasTrans <= $plazo) {
            return EstadoVigencia::VIGENTE;
        }

        if ($diasTrans <= $plazo * 2) {
            return EstadoVigencia::PUEDE_ESTAR_DESACTUALIZADO;
        }

        return EstadoVigencia::HISTORIAL;
    }
}
