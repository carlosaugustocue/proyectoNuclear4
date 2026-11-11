<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Services;

use App\Domains\Precios\Domain\ValueObjects\AporteCorroboracion;
use App\Domains\Precios\Domain\ValueObjects\EstadoConfianza;
use Brick\Math\BigDecimal;

final class Corroboracion
{
    private const VENTANA_HORAS    = 48;
    private const MAX_DIFERENCIA   = 0.05;  // 5 %

    /**
     * @param list<AporteCorroboracion> $aportes
     */
    public static function evaluar(array $aportes, string $precioBase): EstadoConfianza
    {
        $ahora    = new \DateTimeImmutable();
        $ventana  = self::VENTANA_HORAS;
        $limite   = $ahora->modify("-{$ventana} hours");
        $base     = BigDecimal::of($precioBase);
        $usuariosValidos = [];

        foreach ($aportes as $aporte) {
            if ($aporte->registradoEn < $limite) {
                continue; // fuera de ventana
            }

            if (in_array($aporte->userId, $usuariosValidos, true)) {
                continue; // mismo usuario
            }

            $diferencia = $base->isZero()
                ? 0.0
                : abs((float) BigDecimal::of($aporte->monto)->minus($base)->dividedBy($base, 6, \Brick\Math\RoundingMode::HALF_UP)->__toString());

            if ($diferencia > self::MAX_DIFERENCIA) {
                continue; // diferencia > 5%
            }

            $usuariosValidos[] = $aporte->userId;
        }

        return count($usuariosValidos) >= 2
            ? EstadoConfianza::CONFIABLE
            : EstadoConfianza::NO_CORROBORADO;
    }
}
