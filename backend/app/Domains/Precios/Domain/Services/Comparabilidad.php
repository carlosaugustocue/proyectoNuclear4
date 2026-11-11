<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Services;

use App\Domains\Precios\Domain\Exceptions\UnidadesIncompatiblesException;

final class Comparabilidad
{
    private const GRUPOS = [
        'kg'     => 'masa',
        'g'      => 'masa',
        'L'      => 'volumen',
        'l'      => 'volumen',
        'mL'     => 'volumen',
        'ml'     => 'volumen',
        'unidad' => 'unidad',
    ];

    public static function sonComparables(string $unidadA, string $unidadB): bool
    {
        $grupoA = self::GRUPOS[$unidadA] ?? null;
        $grupoB = self::GRUPOS[$unidadB] ?? null;

        if ($grupoA === null || $grupoB === null) {
            return false;
        }

        return $grupoA === $grupoB;
    }

    public static function validar(string $unidadA, string $unidadB): void
    {
        if (! self::sonComparables($unidadA, $unidadB)) {
            throw new UnidadesIncompatiblesException(
                "Las unidades '{$unidadA}' y '{$unidadB}' no son comparables"
            );
        }
    }
}
