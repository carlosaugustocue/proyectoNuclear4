<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Services;

use App\Domains\Precios\Domain\ValueObjects\CuentaContribuidora;
use App\Domains\Precios\Domain\ValueObjects\PermisoAporte;

final class PoliticaReputacion
{
    private const MIN_DIAS_CUENTA  = 7;
    private const MIN_APORTES      = 5;

    public static function evaluar(CuentaContribuidora $cuenta): PermisoAporte
    {
        if ($cuenta->suspendida) {
            return PermisoAporte::RECHAZADO;
        }

        $diasDesdeCreacion = (new \DateTimeImmutable())->diff($cuenta->creadaEn)->days;

        if ($diasDesdeCreacion < self::MIN_DIAS_CUENTA
            || $cuenta->aportesConfirmados < self::MIN_APORTES) {
            return PermisoAporte::REQUIERE_CORROBORACION;
        }

        return PermisoAporte::PUEDE_ACTUALIZAR_VIGENTE;
    }
}
