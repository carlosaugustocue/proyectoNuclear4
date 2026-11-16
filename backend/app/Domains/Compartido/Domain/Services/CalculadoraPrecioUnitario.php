<?php

declare(strict_types=1);

namespace App\Domains\Compartido\Domain\Services;

use App\Domains\Compartido\Domain\ValueObjects\PrecioUnitario;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

/**
 * RN-01 + RN-02: precio_unidad = precio / (cantidad × unidades_por_empaque × factor_a_base)
 * con aritmética decimal exacta (brick/math). Solo el resultado final se lleva a 2 cifras;
 * el redondeo al peso es responsabilidad de la presentación (cliente).
 */
final class CalculadoraPrecioUnitario
{
    /** Unidades del contrato (UnidadMedida) → [factor exacto, unidad base]. */
    private const UNIDADES = [
        'G'      => ['0.001', 'KG'],
        'KG'     => ['1', 'KG'],
        'ML'     => ['0.001', 'L'],
        'L'      => ['1', 'L'],
        'UNIDAD' => ['1', 'UNIDAD'],
    ];

    public static function calcular(string $precio, string $cantidad, string $unidad, int $unidadesPorEmpaque): PrecioUnitario
    {
        $config = self::UNIDADES[strtoupper($unidad)] ?? null;
        if ($config === null) {
            throw new InvalidArgumentException("Unidad desconocida: {$unidad}");
        }
        if ($unidadesPorEmpaque < 1) {
            throw new InvalidArgumentException('Las unidades por empaque deben ser al menos 1');
        }

        $cantidadBase = BigDecimal::of($cantidad)
            ->multipliedBy($unidadesPorEmpaque)
            ->multipliedBy($config[0]);

        if ($cantidadBase->isLessThanOrEqualTo(0)) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que cero');
        }

        $porUnidad = BigDecimal::of($precio)->dividedBy($cantidadBase, 2, RoundingMode::HALF_UP);

        return new PrecioUnitario((string) $porUnidad, $config[1]);
    }
}
