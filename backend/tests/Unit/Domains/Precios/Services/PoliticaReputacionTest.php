<?php

declare(strict_types=1);

use App\Domains\Precios\Domain\Services\PoliticaReputacion;
use App\Domains\Precios\Domain\ValueObjects\CuentaContribuidora;
use App\Domains\Precios\Domain\ValueObjects\PermisoAporte;

describe('U-07 PoliticaReputacion', function () {

    it('cuenta nueva (<7 días) requiere corroboración', function () {
        $cuenta = new CuentaContribuidora(
            id:                 'user-nuevo',
            creadaEn:           new DateTimeImmutable('-3 days'),
            aportesConfirmados: 10,
            suspendida:         false,
        );
        $permiso = PoliticaReputacion::evaluar($cuenta);
        expect($permiso)->toBe(PermisoAporte::REQUIERE_CORROBORACION);
    });

    it('cuenta con <5 aportes confirmados requiere corroboración', function () {
        $cuenta = new CuentaContribuidora(
            id:                 'user-pocos',
            creadaEn:           new DateTimeImmutable('-30 days'),
            aportesConfirmados: 3,
            suspendida:         false,
        );
        $permiso = PoliticaReputacion::evaluar($cuenta);
        expect($permiso)->toBe(PermisoAporte::REQUIERE_CORROBORACION);
    });

    it('cuenta con ≥7 días y ≥5 aportes puede cambiar precio vigente', function () {
        $cuenta = new CuentaContribuidora(
            id:                 'user-confiable',
            creadaEn:           new DateTimeImmutable('-30 days'),
            aportesConfirmados: 5,
            suspendida:         false,
        );
        $permiso = PoliticaReputacion::evaluar($cuenta);
        expect($permiso)->toBe(PermisoAporte::PUEDE_ACTUALIZAR_VIGENTE);
    });

    it('cuenta suspendida → rechazado', function () {
        $cuenta = new CuentaContribuidora(
            id:                 'user-ban',
            creadaEn:           new DateTimeImmutable('-60 days'),
            aportesConfirmados: 100,
            suspendida:         true,
        );
        $permiso = PoliticaReputacion::evaluar($cuenta);
        expect($permiso)->toBe(PermisoAporte::RECHAZADO);
    });

})->group('domain');
