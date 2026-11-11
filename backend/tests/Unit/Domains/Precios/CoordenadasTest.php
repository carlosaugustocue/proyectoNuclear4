<?php

declare(strict_types=1);

use App\Domains\Precios\Domain\ValueObjects\Coordenadas;

describe('Coordenadas (Value Object)', function () {

    it('se puede crear con latitud y longitud válidas', function () {
        $coords = Coordenadas::de(4.5339, -75.6811);

        expect($coords->latitud())->toBe(4.5339)
            ->and($coords->longitud())->toBe(-75.6811);
    });

    it('no acepta latitud fuera de rango [-90, 90]', function () {
        expect(fn () => Coordenadas::de(91.0, -75.6811))
            ->toThrow(InvalidArgumentException::class, 'Latitud inválida');

        expect(fn () => Coordenadas::de(-91.0, -75.6811))
            ->toThrow(InvalidArgumentException::class, 'Latitud inválida');
    });

    it('no acepta longitud fuera de rango [-180, 180]', function () {
        expect(fn () => Coordenadas::de(4.5339, 181.0))
            ->toThrow(InvalidArgumentException::class, 'Longitud inválida');

        expect(fn () => Coordenadas::de(4.5339, -181.0))
            ->toThrow(InvalidArgumentException::class, 'Longitud inválida');
    });

    it('dos coordenadas iguales son equivalentes', function () {
        $a = Coordenadas::de(4.5339, -75.6811);
        $b = Coordenadas::de(4.5339, -75.6811);

        expect($a->equals($b))->toBeTrue();
    });

    it('se serializa al formato WKT para PostGIS', function () {
        $coords = Coordenadas::de(4.5339, -75.6811);

        expect($coords->toWkt())->toBe('POINT(-75.6811 4.5339)');
    });

})->group('domain');
