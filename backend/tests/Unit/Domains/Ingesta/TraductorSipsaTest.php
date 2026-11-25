<?php

declare(strict_types=1);

use App\Domains\Ingesta\Domain\Exceptions\FilaSipsaInvalidaException;
use App\Domains\Ingesta\Domain\Services\TraductorSipsa;

/*
 * Capa anticorrupción de SIPSA (RF-25, RF-26, ADR-0010): traduce una fila del anexo de precios
 * mayoristas del DANE al modelo de la plataforma. SIPSA publica precios por kilogramo, salvo lo que
 * dice su nota al pie: «los precios reportados para los huevos y el bocadillo son $/unidad y los del
 * aceite, el vinagre y el jugo de frutas $/litro».
 */

/** @param array<string, string> $cambios */
function filaSipsa(array $cambios = []): array
{
    return array_merge([
        'fecha'      => '2026-10-01',
        'grupo'      => 'VERDURAS Y HORTALIZAS',
        'producto'   => 'Tomate chonto',
        'codigo_cpc' => '0123401',
        'mercado'    => 'Armenia, Mercar',
        'precio'     => '4052',
    ], $cambios);
}

describe('U-11 TraductorSipsa — categoría, vigencia y unidad base', function () {
    it('una verdura queda en «Frutas y verduras», perecedera y por kilogramo', function () {
        $o = TraductorSipsa::traducir(filaSipsa());

        expect($o->producto)->toBe('Tomate chonto')
            ->and($o->categoria)->toBe('Frutas y verduras')
            ->and($o->tipoVigencia)->toBe('PERECEDERO')
            ->and($o->unidadBase)->toBe('KG')
            ->and($o->mercado)->toBe('Armenia, Mercar')
            ->and($o->codigoCpc)->toBe('0123401')
            ->and($o->mes->format('Y-m-d'))->toBe('2026-10-01')
            ->and($o->precio)->toBe('4052');
    });

    it('frutas y tubérculos también van a «Frutas y verduras»', function (string $grupo) {
        expect(TraductorSipsa::traducir(filaSipsa(['grupo' => $grupo]))->categoria)->toBe('Frutas y verduras');
    })->with(['FRUTAS', 'TUBERCULOS, RAICES Y PLATANOS']);

    it('granos y procesados son abarrotes (plazo de vigencia de 7 días, RN-04)', function (string $grupo, string $categoria) {
        $o = TraductorSipsa::traducir(filaSipsa(['grupo' => $grupo, 'producto' => 'Arroz de primera']));

        expect($o->categoria)->toBe($categoria)->and($o->tipoVigencia)->toBe('ABARROTE');
    })->with([['GRANOS Y CEREALES', 'Granos'], ['PROCESADOS', 'Despensa']]);

    it('carnes, pescados y lácteos son perecederos', function (string $grupo, string $categoria) {
        $o = TraductorSipsa::traducir(filaSipsa(['grupo' => $grupo, 'producto' => 'Queso campesino']));

        expect($o->categoria)->toBe($categoria)->and($o->tipoVigencia)->toBe('PERECEDERO');
    })->with([['CARNES', 'Carnes'], ['PESCADOS', 'Pescados'], ['LACTEOS Y HUEVOS', 'Lácteos']]);

    it('la leche en polvo es un abarrote aunque esté en lácteos', function () {
        $o = TraductorSipsa::traducir(filaSipsa(['grupo' => 'LACTEOS Y HUEVOS', 'producto' => 'Leche en polvo']));

        expect($o->tipoVigencia)->toBe('ABARROTE');
    });

    it('los huevos y el bocadillo se publican por unidad', function (string $grupo, string $producto) {
        expect(TraductorSipsa::traducir(filaSipsa(['grupo' => $grupo, 'producto' => $producto]))->unidadBase)->toBe('UNIDAD');
    })->with([['LACTEOS Y HUEVOS', 'Huevo rojo AA'], ['PROCESADOS', 'Bocadillo veleño']]);

    it('el aceite, el vinagre y el jugo de frutas se publican por litro', function (string $producto) {
        expect(TraductorSipsa::traducir(filaSipsa(['grupo' => 'PROCESADOS', 'producto' => $producto]))->unidadBase)->toBe('L');
    })->with(['Aceite vegetal mezcla', 'Aceite de palma', 'Vinagre', 'Jugo de frutas']);

    it('el jugo instantáneo en sobre no es jugo de frutas: va por kilogramo', function () {
        expect(TraductorSipsa::traducir(filaSipsa(['grupo' => 'PROCESADOS', 'producto' => 'Jugo instantáneo (sobre)']))->unidadBase)->toBe('KG');
    });

    it('los aceites van a «Aceites» y el café y el chocolate a «Bebidas»', function (string $producto, string $categoria) {
        expect(TraductorSipsa::traducir(filaSipsa(['grupo' => 'PROCESADOS', 'producto' => $producto]))->categoria)->toBe($categoria);
    })->with([['Aceite de palma', 'Aceites'], ['Café molido', 'Bebidas'], ['Chocolate amargo', 'Bebidas']]);

    it('normaliza los espacios del nombre', function () {
        expect(TraductorSipsa::traducir(filaSipsa(['producto' => '  Papa   criolla limpia ']))->producto)->toBe('Papa criolla limpia');
    });

    it('lleva el mes al primer día', function () {
        expect(TraductorSipsa::traducir(filaSipsa(['fecha' => '2026-03-17']))->mes->format('Y-m-d'))->toBe('2026-03-01');
    });

    it('acepta precios con decimales sin pasar por float', function () {
        expect(TraductorSipsa::traducir(filaSipsa(['precio' => '417.75']))->precio)->toBe('417.75');
    });
});

describe('U-11 TraductorSipsa — filas inválidas', function () {
    it('rechaza lo que no sabe traducir, con un motivo claro', function (array $cambios, string $motivo) {
        expect(fn () => TraductorSipsa::traducir(filaSipsa($cambios)))
            ->toThrow(FilaSipsaInvalidaException::class, $motivo);
    })->with([
        'grupo desconocido'  => [['grupo' => 'FLORES'], 'grupo'],
        'producto vacío'     => [['producto' => '   '], 'producto'],
        'precio cero'        => [['precio' => '0'], 'precio'],
        'precio negativo'    => [['precio' => '-1200'], 'precio'],
        'precio no numérico' => [['precio' => 'n.d.'], 'precio'],
        'fecha inválida'     => [['fecha' => '01/10/2026'], 'fecha'],
        'mercado vacío'      => [['mercado' => ''], 'mercado'],
    ]);

    it('rechaza una fila sin columnas obligatorias', function () {
        $fila = filaSipsa();
        unset($fila['precio']);

        expect(fn () => TraductorSipsa::traducir($fila))->toThrow(FilaSipsaInvalidaException::class, 'precio');
    });
});
