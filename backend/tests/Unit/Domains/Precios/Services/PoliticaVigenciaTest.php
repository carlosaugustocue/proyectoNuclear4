<?php

declare(strict_types=1);

use App\Domains\Precios\Domain\Services\PoliticaVigencia;
use App\Domains\Precios\Domain\ValueObjects\CategoriaProducto;
use App\Domains\Precios\Domain\ValueObjects\EstadoVigencia;

describe('U-04 PoliticaVigencia', function () {

    it('precio registrado hace 1 día para perecedero → VIGENTE', function () {
        $registrado = new DateTimeImmutable('-1 day');
        $estado = PoliticaVigencia::evaluar($registrado, CategoriaProducto::PERECEDERO);
        expect($estado)->toBe(EstadoVigencia::VIGENTE);
    });

    it('precio registrado hace 4 días para perecedero → PUEDE_ESTAR_DESACTUALIZADO', function () {
        // Vigencia perecedero = 3 días; entre 1× y 2× (3-6 días) → advertencia
        $registrado = new DateTimeImmutable('-4 days');
        $estado = PoliticaVigencia::evaluar($registrado, CategoriaProducto::PERECEDERO);
        expect($estado)->toBe(EstadoVigencia::PUEDE_ESTAR_DESACTUALIZADO);
    });

    it('precio registrado hace 7 días para perecedero → HISTORIAL', function () {
        // > 2× el plazo (> 6 días) → solo histórico
        $registrado = new DateTimeImmutable('-7 days');
        $estado = PoliticaVigencia::evaluar($registrado, CategoriaProducto::PERECEDERO);
        expect($estado)->toBe(EstadoVigencia::HISTORIAL);
    });

    it('precio registrado hace 5 días para abarrote → VIGENTE', function () {
        // Vigencia abarrote = 7 días
        $registrado = new DateTimeImmutable('-5 days');
        $estado = PoliticaVigencia::evaluar($registrado, CategoriaProducto::ABARROTE);
        expect($estado)->toBe(EstadoVigencia::VIGENTE);
    });

    it('precio registrado hace 10 días para abarrote → PUEDE_ESTAR_DESACTUALIZADO', function () {
        // Entre 7 y 14 días → advertencia
        $registrado = new DateTimeImmutable('-10 days');
        $estado = PoliticaVigencia::evaluar($registrado, CategoriaProducto::ABARROTE);
        expect($estado)->toBe(EstadoVigencia::PUEDE_ESTAR_DESACTUALIZADO);
    });

    it('precio registrado hace 15 días para abarrote → HISTORIAL', function () {
        $registrado = new DateTimeImmutable('-15 days');
        $estado = PoliticaVigencia::evaluar($registrado, CategoriaProducto::ABARROTE);
        expect($estado)->toBe(EstadoVigencia::HISTORIAL);
    });

})->group('domain');
