<?php

declare(strict_types=1);

use App\Domains\Precios\Domain\Services\Comparabilidad;

describe('U-03 Comparabilidad', function () {

    it('misma unidad base → comparables', function () {
        expect(Comparabilidad::sonComparables('kg', 'kg'))->toBeTrue();
    });

    it('kg vs g → comparables (normalizables a kg)', function () {
        expect(Comparabilidad::sonComparables('kg', 'g'))->toBeTrue();
    });

    it('mL vs L → comparables (normalizables a L)', function () {
        expect(Comparabilidad::sonComparables('mL', 'L'))->toBeTrue();
    });

    it('kg vs unidad → no comparables', function () {
        expect(Comparabilidad::sonComparables('kg', 'unidad'))->toBeFalse();
    });

    it('kg vs mL → no comparables (masa vs volumen)', function () {
        expect(Comparabilidad::sonComparables('kg', 'mL'))->toBeFalse();
    });

    it('lanza excepción al comparar kg vs unidad de forma estricta', function () {
        expect(fn () => Comparabilidad::validar('kg', 'unidad'))
            ->toThrow(
                \App\Domains\Precios\Domain\Exceptions\UnidadesIncompatiblesException::class
            );
    });

})->group('domain');
