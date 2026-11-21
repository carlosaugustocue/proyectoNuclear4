<?php

declare(strict_types=1);

use App\Domains\Canasta\Domain\Services\CalculadoraCanasta;
use App\Domains\Canasta\Domain\ValueObjects\ItemCanasta;
use App\Domains\Canasta\Domain\ValueObjects\OfertaProducto;
use App\Domains\Canasta\Domain\ValueObjects\ResultadoEstablecimiento;

/** RN-12: subtotal = menor precio por unidad base vigente × cantidad; los faltantes no se suman. */
describe('U-11 CalculadoraCanasta', function () {

    $porId = function (array $resultados): array {
        $mapa = [];
        foreach ($resultados as $r) {
            $mapa[$r->establecimientoId] = $r;
        }

        return $mapa;
    };

    it('calcula el subtotal con el precio por unidad base y la cantidad normalizada', function () use ($porId) {
        $r = $porId(CalculadoraCanasta::calcular(
            [new ItemCanasta('arroz', '0.5', 'kg'), new ItemCanasta('leche', '2', 'L')],
            [new OfertaProducto('arroz', 'e1', 'Tienda Uno', '4200.00'), new OfertaProducto('leche', 'e1', 'Tienda Uno', '3990.50')],
        ))['e1'];

        expect($r->total)->toBe('10081.00')      // 2100.00 + 7981.00
            ->and($r->lineas)->toHaveCount(2)
            ->and($r->lineas[0]->subtotal)->toBe('2100.00')
            ->and($r->faltantes)->toBe([])
            ->and($r->completa())->toBeTrue();
    });

    it('usa aritmética decimal exacta, sin errores de coma flotante (RN-02)', function () use ($porId) {
        $r = $porId(CalculadoraCanasta::calcular(
            [new ItemCanasta('a', '0.1', 'kg'), new ItemCanasta('b', '0.2', 'kg')],
            [new OfertaProducto('a', 'e1', 'T', '1000.00'), new OfertaProducto('b', 'e1', 'T', '1000.00')],
        ))['e1'];

        expect($r->total)->toBe('300.00');
    });

    it('señala los productos sin precio vigente y NO los suma como cero', function () use ($porId) {
        $r = $porId(CalculadoraCanasta::calcular(
            [new ItemCanasta('arroz', '1', 'kg'), new ItemCanasta('aceite', '1', 'L'), new ItemCanasta('huevos', '30', 'unidad')],
            [new OfertaProducto('arroz', 'e1', 'Tienda Uno', '4200.00')],
        ))['e1'];

        expect($r->total)->toBe('4200.00')
            ->and($r->faltantes)->toBe(['aceite', 'huevos'])
            ->and($r->disponibles())->toBe(1)
            ->and($r->totalProductos)->toBe(3)
            ->and($r->completa())->toBeFalse();
    });

    it('con varias presentaciones en un establecimiento usa la de menor precio por unidad', function () use ($porId) {
        $r = $porId(CalculadoraCanasta::calcular(
            [new ItemCanasta('arroz', '1', 'kg')],
            [new OfertaProducto('arroz', 'e1', 'T', '4500.00'), new OfertaProducto('arroz', 'e1', 'T', '3800.00')],
        ))['e1'];

        expect($r->total)->toBe('3800.00')->and($r->lineas)->toHaveCount(1);
    });

    it('calcula cada establecimiento por separado con sus propios faltantes', function () use ($porId) {
        $r = $porId(CalculadoraCanasta::calcular(
            [new ItemCanasta('arroz', '1', 'kg'), new ItemCanasta('leche', '1', 'L')],
            [
                new OfertaProducto('arroz', 'e1', 'Uno', '4000.00'),
                new OfertaProducto('leche', 'e1', 'Uno', '3000.00'),
                new OfertaProducto('leche', 'e2', 'Dos', '2500.00'),
            ],
        ));

        expect($r)->toHaveCount(2)
            ->and($r['e1']->total)->toBe('7000.00')->and($r['e1']->faltantes)->toBe([])
            ->and($r['e2']->total)->toBe('2500.00')->and($r['e2']->faltantes)->toBe(['arroz']);
    });

    it('ignora ofertas de productos que no están en la canasta', function () use ($porId) {
        $r = $porId(CalculadoraCanasta::calcular(
            [new ItemCanasta('arroz', '1', 'kg')],
            [new OfertaProducto('arroz', 'e1', 'T', '4000.00'), new OfertaProducto('cafe', 'e1', 'T', '20000.00')],
        ))['e1'];

        expect($r->total)->toBe('4000.00')->and($r->lineas)->toHaveCount(1);
    });

    it('no incluye establecimientos sin ningún producto de la canasta', function () {
        $r = CalculadoraCanasta::calcular([new ItemCanasta('arroz', '1', 'kg')], []);

        expect($r)->toBe([]);
    });

    it('devuelve ResultadoEstablecimiento con el nombre del establecimiento', function () {
        $r = CalculadoraCanasta::calcular([new ItemCanasta('arroz', '1', 'kg')], [new OfertaProducto('arroz', 'e1', 'Tienda Uno', '4000.00')]);

        expect($r[0])->toBeInstanceOf(ResultadoEstablecimiento::class)
            ->and($r[0]->nombre)->toBe('Tienda Uno');
    });
});
