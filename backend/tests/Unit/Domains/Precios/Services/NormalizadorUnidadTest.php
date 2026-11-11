<?php

declare(strict_types=1);

use App\Domains\Precios\Domain\Services\NormalizadorUnidad;
use App\Domains\Precios\Domain\ValueObjects\CantidadNormalizada;

describe('U-01 NormalizadorUnidad', function () {

    it('convierte gramos a kilogramos (×0.001)', function () {
        $r = NormalizadorUnidad::normalizar('500', 'g');
        expect($r->cantidad)->toBe(0.5)
            ->and($r->unidadBase)->toBe('kg');
    });

    it('convierte mililitros a litros (×0.001)', function () {
        $r = NormalizadorUnidad::normalizar('330', 'mL');
        expect(round($r->cantidad, 5))->toBe(round(0.33, 5))
            ->and($r->unidadBase)->toBe('L');
    });

    it('mantiene kg sin conversión (×1)', function () {
        $r = NormalizadorUnidad::normalizar('1', 'kg');
        expect($r->cantidad)->toBe(1.0)
            ->and($r->unidadBase)->toBe('kg');
    });

    it('mantiene L sin conversión (×1)', function () {
        $r = NormalizadorUnidad::normalizar('2', 'L');
        expect($r->cantidad)->toBe(2.0)
            ->and($r->unidadBase)->toBe('L');
    });

    it('mantiene unidad sin conversión (×1)', function () {
        $r = NormalizadorUnidad::normalizar('1', 'unidad');
        expect($r->cantidad)->toBe(1.0)
            ->and($r->unidadBase)->toBe('unidad');
    });

    it('descompone multiempaque (6×330mL → 1.98 L)', function () {
        $r = NormalizadorUnidad::normalizar('330', 'mL', multiempaque: 6);
        expect(round($r->cantidad, 5))->toBe(round(1.98, 5))
            ->and($r->unidadBase)->toBe('L');
    });

    it('lanza excepción si cantidad es cero', function () {
        expect(fn () => NormalizadorUnidad::normalizar('0', 'kg'))
            ->toThrow(InvalidArgumentException::class, 'La cantidad debe ser mayor que cero');
    });

    it('lanza excepción si unidad es desconocida', function () {
        expect(fn () => NormalizadorUnidad::normalizar('1', 'oz'))
            ->toThrow(InvalidArgumentException::class, 'Unidad desconocida');
    });

})->group('domain');
