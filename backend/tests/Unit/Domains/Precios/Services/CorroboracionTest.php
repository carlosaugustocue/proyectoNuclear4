<?php

declare(strict_types=1);

use App\Domains\Precios\Domain\Services\Corroboracion;
use App\Domains\Precios\Domain\ValueObjects\AporteCorroboracion;
use App\Domains\Precios\Domain\ValueObjects\EstadoConfianza;

describe('U-05 Corroboracion', function () {

    it('2 reportantes distintos, diferencia ≤5%, dentro de 48h → CONFIABLE', function () {
        $ahora = new DateTimeImmutable();
        $aportes = [
            new AporteCorroboracion('user-A', '3000', $ahora->modify('-1 hour')),
            new AporteCorroboracion('user-B', '3050', $ahora),          // +1.67%
        ];

        $estado = Corroboracion::evaluar($aportes, precioBase: '3000');
        expect($estado)->toBe(EstadoConfianza::CONFIABLE);
    });

    it('mismo reportante dos veces no cuenta como corroboración', function () {
        $ahora = new DateTimeImmutable();
        $aportes = [
            new AporteCorroboracion('user-A', '3000', $ahora->modify('-1 hour')),
            new AporteCorroboracion('user-A', '3000', $ahora),         // mismo user
        ];

        $estado = Corroboracion::evaluar($aportes, precioBase: '3000');
        expect($estado)->toBe(EstadoConfianza::NO_CORROBORADO);
    });

    it('diferencia >5% entre aportes → NO_CORROBORADO', function () {
        $ahora = new DateTimeImmutable();
        $aportes = [
            new AporteCorroboracion('user-A', '3000', $ahora->modify('-1 hour')),
            new AporteCorroboracion('user-B', '3200', $ahora),          // +6.67%
        ];

        $estado = Corroboracion::evaluar($aportes, precioBase: '3000');
        expect($estado)->toBe(EstadoConfianza::NO_CORROBORADO);
    });

    it('aporte fuera de ventana de 48h no cuenta', function () {
        $ahora = new DateTimeImmutable();
        $aportes = [
            new AporteCorroboracion('user-A', '3000', $ahora->modify('-49 hours')),
            new AporteCorroboracion('user-B', '3010', $ahora),
        ];

        $estado = Corroboracion::evaluar($aportes, precioBase: '3000');
        expect($estado)->toBe(EstadoConfianza::NO_CORROBORADO);
    });

    it('solo un aporte válido → NO_CORROBORADO', function () {
        $ahora = new DateTimeImmutable();
        $aportes = [
            new AporteCorroboracion('user-A', '3000', $ahora),
        ];

        $estado = Corroboracion::evaluar($aportes, precioBase: '3000');
        expect($estado)->toBe(EstadoConfianza::NO_CORROBORADO);
    });

})->group('domain');
