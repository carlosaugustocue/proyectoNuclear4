<?php

declare(strict_types=1);

use App\Domains\Precios\Domain\ValueObjects\Precio;
use Brick\Money\Money;

describe('Precio (Value Object)', function () {

    it('se puede crear con un valor positivo en COP', function () {
        $precio = Precio::de('15000', 'COP');

        expect($precio->monto())->toBe('15000.00')
            ->and($precio->moneda())->toBe('COP');
    });

    it('no acepta valores negativos', function () {
        expect(fn () => Precio::de('-500', 'COP'))
            ->toThrow(InvalidArgumentException::class, 'El precio debe ser mayor que cero');
    });

    it('no acepta valor cero', function () {
        expect(fn () => Precio::de('0', 'COP'))
            ->toThrow(InvalidArgumentException::class, 'El precio debe ser mayor que cero');
    });

    it('dos precios iguales son equivalentes', function () {
        $a = Precio::de('15000', 'COP');
        $b = Precio::de('15000', 'COP');

        expect($a->equals($b))->toBeTrue();
    });

    it('dos precios distintos no son equivalentes', function () {
        $a = Precio::de('15000', 'COP');
        $b = Precio::de('16000', 'COP');

        expect($a->equals($b))->toBeFalse();
    });

    it('expone el Money subyacente', function () {
        $precio = Precio::de('15000', 'COP');

        expect($precio->toMoney())->toBeInstanceOf(Money::class);
    });

})->group('domain');
