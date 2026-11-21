<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Domain\Services;

use App\Domains\Comercial\Domain\Exceptions\PromocionInvalidaException;
use App\Domains\Comercial\Domain\ValueObjects\Promocion;
use App\Domains\Comercial\Domain\ValueObjects\TipoPromocion;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use DateTimeImmutable;

/** RN-14 ⚑: reúne todos los errores de una promoción antes de rechazarla. */
final class ValidadorPromocion
{
    public const DESCUENTO_MINIMO = 1;
    public const DESCUENTO_MAXIMO = 90;
    public const DURACION_MAXIMA_DIAS = 31;

    public static function validar(
        string            $tipo,
        ?string           $valor,
        ?int              $pague,
        ?int              $lleve,
        DateTimeImmutable $inicio,
        DateTimeImmutable $fin,
    ): Promocion {
        $tipoPromocion = TipoPromocion::tryFrom($tipo);
        $errores = $tipoPromocion === null
            ? ["Tipo de promoción desconocido: {$tipo}."]
            : self::erroresDeCondiciones($tipoPromocion, $valor, $pague, $lleve);

        $errores = [...$errores, ...self::erroresDeVigencia($inicio, $fin)];

        if ($errores !== [] || $tipoPromocion === null) {
            throw new PromocionInvalidaException($errores);
        }

        $esPagueLleve = $tipoPromocion === TipoPromocion::PAGUE_X_LLEVE_Y;

        return new Promocion(
            $tipoPromocion,
            $esPagueLleve ? null : $valor,
            $esPagueLleve ? $pague : null,
            $esPagueLleve ? $lleve : null,
            $inicio,
            $fin,
        );
    }

    /** @return list<string> */
    private static function erroresDeCondiciones(TipoPromocion $tipo, ?string $valor, ?int $pague, ?int $lleve): array
    {
        return match ($tipo) {
            TipoPromocion::DESCUENTO_PORCENTUAL => self::enRango($valor, self::DESCUENTO_MINIMO, self::DESCUENTO_MAXIMO)
                ? [] : ['El descuento debe estar entre 1 % y 90 %.'],
            TipoPromocion::PRECIO_ESPECIAL => self::esPositivo($valor)
                ? [] : ['El precio especial debe ser mayor que cero.'],
            TipoPromocion::PAGUE_X_LLEVE_Y => ($pague !== null && $lleve !== null && $pague >= 1 && $lleve > $pague)
                ? [] : ['En «pague X lleve Y», Y debe ser mayor que X y X al menos 1.'],
        };
    }

    /** @return list<string> */
    private static function erroresDeVigencia(DateTimeImmutable $inicio, DateTimeImmutable $fin): array
    {
        if ($fin <= $inicio) {
            return ['La fecha de fin debe ser posterior a la de inicio.'];
        }
        if ($fin > $inicio->modify('+' . self::DURACION_MAXIMA_DIAS . ' days')) {
            return ['Una promoción no puede durar más de 31 días.'];
        }

        return [];
    }

    private static function enRango(?string $valor, int $minimo, int $maximo): bool
    {
        $numero = self::decimal($valor);

        return $numero !== null && $numero->isGreaterThanOrEqualTo($minimo) && $numero->isLessThanOrEqualTo($maximo);
    }

    private static function esPositivo(?string $valor): bool
    {
        return self::decimal($valor)?->isPositive() ?? false;
    }

    private static function decimal(?string $valor): ?BigDecimal
    {
        if ($valor === null) {
            return null;
        }
        try {
            return BigDecimal::of($valor);
        } catch (MathException) {
            return null;
        }
    }
}
