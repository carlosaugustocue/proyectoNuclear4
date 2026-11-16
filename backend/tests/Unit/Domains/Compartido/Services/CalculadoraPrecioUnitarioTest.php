<?php

declare(strict_types=1);

use App\Domains\Compartido\Domain\Services\CalculadoraPrecioUnitario;

/*
 * RN-01 (normalización) y RN-02 (precisión decimal exacta) con las unidades del
 * contrato (G, KG, ML, L, UNIDAD). El redondeo a pesos ocurre solo al mostrar.
 */
describe('CalculadoraPrecioUnitario (RN-01, RN-02)', function () {
    it('paca 6 x 330 mL a $9.900 → $5.000 por litro (ejemplo RN-01)', function () {
        $r = CalculadoraPrecioUnitario::calcular('9900', '330', 'ML', 6);

        expect($r->precioPorUnidad)->toBe('5000.00')->and($r->unidadBase)->toBe('L');
    });

    it('$2.990 / 0,454 kg con decimales exactos (ejemplo RN-02)', function () {
        $r = CalculadoraPrecioUnitario::calcular('2990', '0.454', 'KG', 1);

        expect($r->precioPorUnidad)->toBe('6585.90')->and($r->unidadBase)->toBe('KG');
    });

    it('500 g a $2.990 → $5.980 por kilogramo', function () {
        expect(CalculadoraPrecioUnitario::calcular('2990', '500', 'G', 1)->precioPorUnidad)->toBe('5980.00');
    });

    it('cubeta de 30 huevos a $15.000 → $500 por unidad', function () {
        $r = CalculadoraPrecioUnitario::calcular('15000', '30', 'UNIDAD', 1);

        expect($r->precioPorUnidad)->toBe('500.00')->and($r->unidadBase)->toBe('UNIDAD');
    });

    it('no acumula error de coma flotante (0,1 + 0,2)', function () {
        // 3 x 100 mL a $0.30 → $1 por litro exacto
        expect(CalculadoraPrecioUnitario::calcular('0.30', '100', 'ML', 3)->precioPorUnidad)->toBe('1.00');
    });

    it('rechaza cantidad cero o unidades desconocidas', function (string $cantidad, string $unidad, int $empaque) {
        CalculadoraPrecioUnitario::calcular('1000', $cantidad, $unidad, $empaque);
    })->with([
        'cantidad cero'      => ['0', 'G', 1],
        'unidad desconocida' => ['1', 'ONZA', 1],
        'empaque cero'       => ['1', 'KG', 0],
    ])->throws(InvalidArgumentException::class);
})->group('domain');
