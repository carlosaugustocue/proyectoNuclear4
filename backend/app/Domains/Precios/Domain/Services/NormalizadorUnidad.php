<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Services;

use App\Domains\Precios\Domain\ValueObjects\CantidadNormalizada;
use InvalidArgumentException;

final class NormalizadorUnidad
{
    /** Factor de conversión a la unidad base y cuál es esa unidad base */
    private const UNIDADES = [
        'g'       => ['factor' => 0.001,  'base' => 'kg'],
        'kg'      => ['factor' => 1.0,    'base' => 'kg'],
        'mL'      => ['factor' => 0.001,  'base' => 'L'],
        'ml'      => ['factor' => 0.001,  'base' => 'L'],
        'L'       => ['factor' => 1.0,    'base' => 'L'],
        'l'       => ['factor' => 1.0,    'base' => 'L'],
        'unidad'  => ['factor' => 1.0,    'base' => 'unidad'],
    ];

    public static function normalizar(
        string $cantidad,
        string $unidad,
        int    $multiempaque = 1,
    ): CantidadNormalizada {
        if ((float) $cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que cero');
        }

        if (! isset(self::UNIDADES[$unidad])) {
            throw new InvalidArgumentException("Unidad desconocida: {$unidad}");
        }

        $config    = self::UNIDADES[$unidad];
        $cantidadN = (float) $cantidad * $config['factor'] * $multiempaque;

        return new CantidadNormalizada($cantidadN, $config['base']);
    }
}
