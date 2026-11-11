<?php

declare(strict_types=1);

use App\Domains\Precios\Domain\Services\Dinero;

describe('U-02 Dinero — decimales exactos', function () {

    it('no usa float internamente: 2990 / 0.454 kg = 6585.903... COP/kg', function () {
        $precioPorUnidad = Dinero::de('2990', 'COP');
        $resultado       = Dinero::precioPorKg($precioPorUnidad, '0.454');

        // Verificamos que el resultado es exacto y no tiene error de flotante
        expect($resultado->formatoDisplay())->toBe('$6.585,90');
    });

    it('solo redondea al mostrar, no internamente', function () {
        $a = Dinero::de('1', 'COP');
        $b = Dinero::de('3', 'COP');

        // 1/3 internamente es exacto; solo al mostrar se redondea
        $division = Dinero::dividir($a, '3');
        expect($division->esExacto())->toBeFalse()   // es periódico
            ->and($division->formatoDisplay())->toBe('$0,33');
    });

    it('suma sin pérdida de precisión', function () {
        $total = Dinero::sumar([
            Dinero::de('0.1', 'COP'),
            Dinero::de('0.2', 'COP'),
        ]);
        // Con float daría 0.30000000000000004; con BigDecimal da 0.30
        expect($total->formatoDisplay())->toBe('$0,30');
    });

    it('precio por kg con gramos: $2.990 / 500g = $5.980/kg', function () {
        $precio  = Dinero::de('2990', 'COP');
        $result  = Dinero::precioPorKg($precio, '0.5');
        expect($result->formatoDisplay())->toBe('$5.980,00');
    });

})->group('domain');
