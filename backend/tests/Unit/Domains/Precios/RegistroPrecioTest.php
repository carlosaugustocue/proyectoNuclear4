<?php

declare(strict_types=1);

use App\Domains\Compartido\Domain\ValueObjects\Precio;
use App\Domains\Precios\Domain\Entities\RegistroPrecio;
use App\Domains\Precios\Domain\Events\PrecioRegistrado;
use App\Domains\Precios\Domain\ValueObjects\FuenteObservacion;

/**
 * Observación de precio (RF-08). Privacidad por diseño (EC-07, R-01):
 * la entidad no tiene ubicación del dispositivo; solo referencia al establecimiento.
 */
function nuevoRegistro(array $over = []): RegistroPrecio
{
    return RegistroPrecio::crear(...array_merge([
        'presentacionId'    => 'pres-arroz-500g',
        'productoId'        => 'prod-arroz',
        'establecimientoId' => 'est-001',
        'precio'            => Precio::de('2990', 'COP'),
        'observadoEn'       => new DateTimeImmutable('-10 minutes'),
        'reportadoPor'      => 'anon-abc',
        'claveIdempotencia' => '0193a1d2-1111-7000-8000-000000000001',
        'fuente'            => FuenteObservacion::REPORTANTE,
    ], $over));
}

describe('RegistroPrecio (Entity)', function () {

    it('se crea con la presentación, el establecimiento y el momento de observación', function () {
        $registro = nuevoRegistro();

        expect($registro->presentacionId())->toBe('pres-arroz-500g')
            ->and($registro->productoId())->toBe('prod-arroz')
            ->and($registro->establecimientoId())->toBe('est-001')
            ->and($registro->precio()->monto())->toBe('2990.00')
            ->and($registro->claveIdempotencia())->toBe('0193a1d2-1111-7000-8000-000000000001')
            ->and($registro->fuente())->toBe(FuenteObservacion::REPORTANTE);
    });

    it('conserva observadoEn aunque el envío ocurra después (sincronización offline, EC-09)', function () {
        $observado = new DateTimeImmutable('-2 hours');

        expect(nuevoRegistro(['observadoEn' => $observado])->observadoEn())->toEqual($observado);
    });

    it('rechaza una observación con fecha futura (más de 5 minutos de tolerancia de reloj)', function () {
        nuevoRegistro(['observadoEn' => new DateTimeImmutable('+1 hour')]);
    })->throws(InvalidArgumentException::class);

    it('acepta un pequeño desfase de reloj del dispositivo (hasta 5 minutos)', function () {
        expect(nuevoRegistro(['observadoEn' => new DateTimeImmutable('+2 minutes')]))->toBeInstanceOf(RegistroPrecio::class);
    });

    it('genera un evento PrecioRegistrado con los datos que necesitan Confianza y Comparación', function () {
        $registro = nuevoRegistro();
        $eventos  = $registro->pullDomainEvents();

        expect($eventos)->toHaveCount(1)
            ->and($eventos[0])->toBeInstanceOf(PrecioRegistrado::class)
            ->and($eventos[0]->registroId)->toBe($registro->id())
            ->and($eventos[0]->presentacionId)->toBe('pres-arroz-500g')
            ->and($eventos[0]->monto)->toBe('2990.00')
            ->and($eventos[0]->fuente)->toBe('REPORTANTE');
    });

    it('el evento no transporta ubicación del dispositivo (privacidad por diseño)', function () {
        $evento = nuevoRegistro()->pullDomainEvents()[0];

        expect(property_exists($evento, 'latitud'))->toBeFalse()
            ->and(property_exists($evento, 'longitud'))->toBeFalse();
    });

    it('los eventos se vacían después de hacer pull', function () {
        $registro = nuevoRegistro();
        $registro->pullDomainEvents();

        expect($registro->pullDomainEvents())->toBeEmpty();
    });

})->group('domain');
