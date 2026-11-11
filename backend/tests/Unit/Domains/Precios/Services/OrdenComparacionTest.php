<?php

declare(strict_types=1);

use App\Domains\Precios\Domain\Services\OrdenComparacion;
use App\Domains\Precios\Domain\ValueObjects\ItemComparacion;

describe('U-10 OrdenComparacion', function () {

    it('ordena por precio/unidad ascendente por defecto', function () {
        $items = [
            new ItemComparacion('est-B', '5.00', '1', 'kg', registradoEn: new DateTimeImmutable('-1 hour'), atributoComercial: null),
            new ItemComparacion('est-A', '3.00', '1', 'kg', registradoEn: new DateTimeImmutable('-2 hours'), atributoComercial: null),
            new ItemComparacion('est-C', '4.00', '1', 'kg', registradoEn: new DateTimeImmutable('-3 hours'), atributoComercial: null),
        ];

        $ordenados = OrdenComparacion::porPrecioUnitario($items);

        expect($ordenados[0]->establecimientoId)->toBe('est-A')
            ->and($ordenados[1]->establecimientoId)->toBe('est-C')
            ->and($ordenados[2]->establecimientoId)->toBe('est-B');
    });

    it('en empate de precio/unidad, el más reciente primero', function () {
        $ahora = new DateTimeImmutable();
        $items = [
            new ItemComparacion('est-A', '3.00', '1', 'kg', registradoEn: $ahora->modify('-2 hours'), atributoComercial: null),
            new ItemComparacion('est-B', '3.00', '1', 'kg', registradoEn: $ahora->modify('-1 hour'), atributoComercial: null),
        ];

        $ordenados = OrdenComparacion::porPrecioUnitario($items);

        expect($ordenados[0]->establecimientoId)->toBe('est-B'); // más reciente primero
    });

    it('atributos comerciales no alteran el orden (AR-02 neutralidad)', function () {
        $items = [
            new ItemComparacion('est-patrocinado', '5.00', '1', 'kg', registradoEn: new DateTimeImmutable(), atributoComercial: 'patrocinado'),
            new ItemComparacion('est-normal',      '3.00', '1', 'kg', registradoEn: new DateTimeImmutable(), atributoComercial: null),
        ];

        $ordenados = OrdenComparacion::porPrecioUnitario($items);

        // El establecimiento patrocinado NO sube porque paga más
        expect($ordenados[0]->establecimientoId)->toBe('est-normal');
    });

    it('ordena por distancia cuando se solicita', function () {
        $items = [
            new ItemComparacion('est-lejano',  '3.00', '1', 'kg', registradoEn: new DateTimeImmutable(), atributoComercial: null, distanciaMetros: 2000),
            new ItemComparacion('est-cercano', '5.00', '1', 'kg', registradoEn: new DateTimeImmutable(), atributoComercial: null, distanciaMetros: 300),
        ];

        $ordenados = OrdenComparacion::porDistancia($items);

        expect($ordenados[0]->establecimientoId)->toBe('est-cercano');
    });

})->group('domain');
