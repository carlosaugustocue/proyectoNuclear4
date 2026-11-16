<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Domain\Services;

use App\Domains\Confianza\Domain\ValueObjects\AporteCorroboracion;
use App\Domains\Confianza\Domain\ValueObjects\EstadoConfianza;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * RN-05 ⚑: si dos o más reportantes independientes registran precios con diferencia
 * ≤ 5 % dentro de una ventana de 48 h, la observación pasa a CONFIABLE.
 */
final class Corroboracion
{
    private const VENTANA_HORAS  = 48;
    private const MAX_DIFERENCIA = '0.05';  // 5 %

    /** @param list<AporteCorroboracion> $aportes */
    public static function evaluar(array $aportes, string $precioBase): EstadoConfianza
    {
        return count(self::corroborantes($aportes, $precioBase)) >= 2
            ? EstadoConfianza::CONFIABLE
            : EstadoConfianza::NO_CORROBORADO;
    }

    /**
     * Aportes que corroboran el precio base: uno por reportante, dentro de la ventana
     * y con diferencia ≤ 5 %.
     *
     * @param  list<AporteCorroboracion> $aportes
     * @return list<AporteCorroboracion>
     */
    public static function corroborantes(array $aportes, string $precioBase): array
    {
        $limite = (new \DateTimeImmutable())->modify('-' . self::VENTANA_HORAS . ' hours');
        $base   = BigDecimal::of($precioBase);
        $porUsuario = [];

        foreach ($aportes as $aporte) {
            if ($aporte->registradoEn < $limite || isset($porUsuario[$aporte->userId])) {
                continue; // fuera de ventana o mismo reportante
            }
            if (! self::dentroDeTolerancia($base, BigDecimal::of($aporte->monto))) {
                continue;
            }
            $porUsuario[$aporte->userId] = $aporte;
        }

        return array_values($porUsuario);
    }

    private static function dentroDeTolerancia(BigDecimal $base, BigDecimal $monto): bool
    {
        if ($base->isZero()) {
            return $monto->isZero();
        }

        return $monto->minus($base)->abs()
            ->dividedBy($base, 6, RoundingMode::HALF_UP)
            ->isLessThanOrEqualTo(self::MAX_DIFERENCIA);
    }
}
