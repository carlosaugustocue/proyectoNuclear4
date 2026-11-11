<?php

declare(strict_types=1);

use App\Domains\Precios\Domain\Entities\RegistroPrecio;
use App\Domains\Precios\Domain\ValueObjects\Precio;
use App\Domains\Precios\Domain\ValueObjects\Coordenadas;

describe('RegistroPrecio (Entity)', function () {

    it('se puede crear con datos válidos', function () {
        $registro = RegistroPrecio::crear(
            productoId: 'prod-001',
            establecimientoId: 'est-001',
            precio: Precio::de('15000', 'COP'),
            coordenadas: Coordenadas::de(4.5339, -75.6811),
            reportadoPor: 'user-001',
        );

        expect($registro->productoId())->toBe('prod-001')
            ->and($registro->establecimientoId())->toBe('est-001')
            ->and($registro->reportadoPor())->toBe('user-001')
            ->and($registro->precio())->toBeInstanceOf(Precio::class);
    });

    it('genera un evento de dominio PrecioRegistrado al crearse', function () {
        $registro = RegistroPrecio::crear(
            productoId: 'prod-001',
            establecimientoId: 'est-001',
            precio: Precio::de('15000', 'COP'),
            coordenadas: Coordenadas::de(4.5339, -75.6811),
            reportadoPor: 'user-001',
        );

        $eventos = $registro->pullDomainEvents();

        expect($eventos)->toHaveCount(1)
            ->and($eventos[0])->toBeInstanceOf(
                \App\Domains\Precios\Domain\Events\PrecioRegistrado::class
            );
    });

    it('los eventos se vacían después de hacer pull', function () {
        $registro = RegistroPrecio::crear(
            productoId: 'prod-001',
            establecimientoId: 'est-001',
            precio: Precio::de('15000', 'COP'),
            coordenadas: Coordenadas::de(4.5339, -75.6811),
            reportadoPor: 'user-001',
        );

        $registro->pullDomainEvents();

        expect($registro->pullDomainEvents())->toBeEmpty();
    });

})->group('domain');
