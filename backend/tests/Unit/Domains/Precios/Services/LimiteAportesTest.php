<?php

declare(strict_types=1);

use App\Domains\Precios\Domain\Services\LimiteAportes;
use App\Domains\Precios\Domain\ValueObjects\ResultadoLimite;

describe('U-08 LimiteAportes', function () {

    it('30 aportes en la última hora → permitido', function () {
        $resultado = LimiteAportes::verificar(aportesUltimaHora: 30);
        expect($resultado)->toBe(ResultadoLimite::PERMITIDO);
    });

    it('31 aportes en la última hora → rechazado (429)', function () {
        $resultado = LimiteAportes::verificar(aportesUltimaHora: 31);
        expect($resultado)->toBe(ResultadoLimite::LIMITE_EXCEDIDO);
    });

    it('mismo producto+establecimiento+día → reemplaza (no suma al conteo)', function () {
        $resultado = LimiteAportes::verificarDuplicado(
            yaExisteHoy: true,
        );
        expect($resultado)->toBe(ResultadoLimite::REEMPLAZAR);
    });

    it('producto+establecimiento distinto en el mismo día → nuevo aporte', function () {
        $resultado = LimiteAportes::verificarDuplicado(
            yaExisteHoy: false,
        );
        expect($resultado)->toBe(ResultadoLimite::PERMITIDO);
    });

})->group('domain');
