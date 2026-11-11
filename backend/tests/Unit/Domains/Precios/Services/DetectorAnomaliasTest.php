<?php

declare(strict_types=1);

use App\Domains\Precios\Domain\Services\DetectorAnomalias;
use App\Domains\Precios\Domain\ValueObjects\EstadoConfianza;

describe('U-06 DetectorAnomalias', function () {

    it('precio dentro del rango MAD (n≥5) → CONFIABLE', function () {
        // precios: 3000, 3010, 2990, 3005, 2995 — mediana ~3000
        $precios = ['3000', '3010', '2990', '3005', '2995'];
        $estado  = DetectorAnomalias::evaluar('3000', $precios);
        expect($estado)->toBe(EstadoConfianza::CONFIABLE);
    });

    it('precio con |M| > 3.5 (n≥5) → POR_VERIFICAR', function () {
        // precios normales ~3000; precio anómalo 6000
        $precios = ['3000', '3010', '2990', '3005', '2995'];
        $estado  = DetectorAnomalias::evaluar('6000', $precios);
        expect($estado)->toBe(EstadoConfianza::POR_VERIFICAR);
    });

    it('n<5 y precio dentro de ±50% del historial → CONFIABLE', function () {
        $precios = ['3000', '3100'];   // solo 2 precios históricos
        $estado  = DetectorAnomalias::evaluar('3200', $precios);
        expect($estado)->toBe(EstadoConfianza::CONFIABLE);
    });

    it('n<5 y precio fuera de ±50% del historial → POR_VERIFICAR', function () {
        $precios = ['3000', '3100'];
        $estado  = DetectorAnomalias::evaluar('5000', $precios);   // +66%
        expect($estado)->toBe(EstadoConfianza::POR_VERIFICAR);
    });

    it('MAD=0 (todos los precios iguales) no produce división por cero', function () {
        $precios = ['3000', '3000', '3000', '3000', '3000'];
        // Con MAD=0 el score no es calculable; precio igual → CONFIABLE
        $estado  = DetectorAnomalias::evaluar('3000', $precios);
        expect($estado)->toBe(EstadoConfianza::CONFIABLE);
    });

    it('MAD=0 y precio diferente → POR_VERIFICAR', function () {
        $precios = ['3000', '3000', '3000', '3000', '3000'];
        $estado  = DetectorAnomalias::evaluar('4000', $precios);
        expect($estado)->toBe(EstadoConfianza::POR_VERIFICAR);
    });

})->group('domain');
