<?php

declare(strict_types=1);

use App\Domains\Confianza\Domain\Services\PoliticaReemplazoVigente;
use App\Domains\Confianza\Domain\ValueObjects\EstadoConfianza;
use App\Domains\Confianza\Domain\ValueObjects\EstadoVigencia;
use App\Domains\Confianza\Domain\ValueObjects\ObservacionVigente;
use App\Domains\Confianza\Domain\ValueObjects\PermisoAporte;

/*
 * RN-07 (peso por reputación) + RN-09: decide si una observación nueva reemplaza
 * al precio vigente que hoy muestra la comparación para ese establecimiento.
 */
function vigente(string $monto = '3000', EstadoConfianza $e = EstadoConfianza::CONFIABLE,
    EstadoVigencia $v = EstadoVigencia::VIGENTE, string $cuando = '-1 day'): ObservacionVigente
{
    return new ObservacionVigente($monto, $e, $v, new DateTimeImmutable($cuando));
}

function decidir(?ObservacionVigente $actual, string $monto, EstadoConfianza $estado, PermisoAporte $permiso,
    string $cuando = 'now'): bool
{
    return PoliticaReemplazoVigente::debeReemplazar($actual, $monto, $estado, $permiso, new DateTimeImmutable($cuando));
}

describe('PoliticaReemplazoVigente (RN-07, RN-09)', function () {
    it('sin precio vigente previo, la observación se publica', function () {
        expect(decidir(null, '3000', EstadoConfianza::NO_CORROBORADO, PermisoAporte::REQUIERE_CORROBORACION))->toBeTrue();
    });

    it('cuenta nueva reporta $1.000 → no reemplaza el vigente confiable (ejemplo RN-07)', function () {
        expect(decidir(vigente('3000'), '1000', EstadoConfianza::NO_CORROBORADO, PermisoAporte::REQUIERE_CORROBORACION))->toBeFalse();
    });

    it('cuenta nueva con un precio consistente (≤ 5 %) sí actualiza el vigente', function () {
        expect(decidir(vigente('3000'), '3100', EstadoConfianza::NO_CORROBORADO, PermisoAporte::REQUIERE_CORROBORACION))->toBeTrue();
    });

    it('una observación corroborada reemplaza aunque venga de cuentas nuevas', function () {
        expect(decidir(vigente('3000'), '3600', EstadoConfianza::CONFIABLE, PermisoAporte::REQUIERE_CORROBORACION))->toBeTrue();
    });

    it('una cuenta con reputación puede cambiar el vigente', function () {
        expect(decidir(vigente('3000'), '3600', EstadoConfianza::NO_CORROBORADO, PermisoAporte::PUEDE_ACTUALIZAR_VIGENTE))->toBeTrue();
    });

    it('si el vigente no está corroborado, la observación más reciente lo reemplaza', function () {
        expect(decidir(vigente('3000', EstadoConfianza::NO_CORROBORADO), '1000', EstadoConfianza::NO_CORROBORADO,
            PermisoAporte::REQUIERE_CORROBORACION))->toBeTrue();
    });

    it('si el vigente ya pasó a historial, cualquier observación nueva lo reemplaza', function () {
        expect(decidir(vigente('3000', EstadoConfianza::CONFIABLE, EstadoVigencia::HISTORIAL, '-20 days'), '1000',
            EstadoConfianza::NO_CORROBORADO, PermisoAporte::REQUIERE_CORROBORACION))->toBeTrue();
    });

    it('una observación más antigua que la vigente no la reemplaza (sincronización offline tardía)', function () {
        expect(decidir(vigente('3000', EstadoConfianza::NO_CORROBORADO, EstadoVigencia::VIGENTE, '-1 hour'), '2900',
            EstadoConfianza::CONFIABLE, PermisoAporte::PUEDE_ACTUALIZAR_VIGENTE, '-3 hours'))->toBeFalse();
    });

    it('una cuenta suspendida nunca reemplaza', function () {
        expect(decidir(null, '3000', EstadoConfianza::CONFIABLE, PermisoAporte::RECHAZADO))->toBeFalse();
    });
})->group('domain');
