<?php

declare(strict_types=1);

namespace App\Domains\Canasta\Domain\Services;

use App\Domains\Canasta\Domain\ValueObjects\ResultadoEstablecimiento;
use Brick\Math\BigDecimal;
use InvalidArgumentException;

/**
 * RN-13 (ADR-0019, ADR-0009): solo criterios declarados al usuario. Ningún atributo comercial
 * interviene; el desempate final por nombre es determinista y neutral.
 */
final class OrdenCanasta
{
    public const DISPONIBILIDAD = 'disponibilidad';
    public const TOTAL          = 'total';

    private const NEUTRALIDAD = 'Ningún comercio paga por su posición.';

    /**
     * @param  list<ResultadoEstablecimiento> $resultados
     * @return list<ResultadoEstablecimiento>
     */
    public static function ordenar(array $resultados, string $criterio): array
    {
        $comparar = match ($criterio) {
            self::DISPONIBILIDAD => static fn (ResultadoEstablecimiento $a, ResultadoEstablecimiento $b): int =>
                [$b->disponibles(), self::monto($a)] <=> [$a->disponibles(), self::monto($b)],
            self::TOTAL => static fn (ResultadoEstablecimiento $a, ResultadoEstablecimiento $b): int =>
                [self::monto($a), $b->disponibles()] <=> [self::monto($b), $a->disponibles()],
            default => throw new InvalidArgumentException("Criterio de orden no declarado: {$criterio}"),
        };

        usort($resultados, static fn (ResultadoEstablecimiento $a, ResultadoEstablecimiento $b): int =>
            $comparar($a, $b) ?: strcmp($a->nombre, $b->nombre));

        return $resultados;
    }

    public static function descripcion(string $criterio): string
    {
        return match ($criterio) {
            self::DISPONIBILIDAD => 'Primero los establecimientos con más productos de la canasta; entre ellos, el de menor total. ' . self::NEUTRALIDAD,
            self::TOTAL          => 'Primero el menor total de los productos disponibles; ante un empate, el que tiene más productos. ' . self::NEUTRALIDAD,
            default              => throw new InvalidArgumentException("Criterio de orden no declarado: {$criterio}"),
        };
    }

    /** Monto en centavos como entero para comparar decimales sin pasar por float. */
    private static function monto(ResultadoEstablecimiento $r): int
    {
        return BigDecimal::of($r->total)->toScale(2)->getUnscaledValue()->toInt();
    }
}
