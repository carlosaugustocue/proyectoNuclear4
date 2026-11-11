<?php

declare(strict_types=1);

use App\Domains\Precios\Domain\Services\SelectorPrecioVigente;
use App\Domains\Precios\Domain\ValueObjects\CandidatoPrecio;
use App\Domains\Precios\Domain\ValueObjects\EstadoConfianza;
use App\Domains\Precios\Domain\ValueObjects\EstadoVigencia;
use App\Domains\Precios\Domain\ValueObjects\ResultadoSelector;

describe('U-09 SelectorPrecioVigente', function () {

    it('CONFIABLE + VIGENTE → mostrar', function () {
        $candidato = new CandidatoPrecio('3000', EstadoConfianza::CONFIABLE, EstadoVigencia::VIGENTE);
        $resultado = SelectorPrecioVigente::seleccionar($candidato);
        expect($resultado)->toBe(ResultadoSelector::MOSTRAR);
    });

    it('POR_VERIFICAR + VIGENTE → mostrar con advertencia', function () {
        $candidato = new CandidatoPrecio('3000', EstadoConfianza::POR_VERIFICAR, EstadoVigencia::VIGENTE);
        $resultado = SelectorPrecioVigente::seleccionar($candidato);
        expect($resultado)->toBe(ResultadoSelector::MOSTRAR_CON_ADVERTENCIA);
    });

    it('NO_CONFIABLE + VIGENTE → no mostrar como vigente', function () {
        $candidato = new CandidatoPrecio('3000', EstadoConfianza::NO_CONFIABLE, EstadoVigencia::VIGENTE);
        $resultado = SelectorPrecioVigente::seleccionar($candidato);
        expect($resultado)->toBe(ResultadoSelector::NO_MOSTRAR);
    });

    it('CONFIABLE + HISTORIAL → no mostrar como vigente', function () {
        $candidato = new CandidatoPrecio('3000', EstadoConfianza::CONFIABLE, EstadoVigencia::HISTORIAL);
        $resultado = SelectorPrecioVigente::seleccionar($candidato);
        expect($resultado)->toBe(ResultadoSelector::NO_MOSTRAR);
    });

    it('CONFIABLE + PUEDE_ESTAR_DESACTUALIZADO → mostrar con advertencia', function () {
        $candidato = new CandidatoPrecio('3000', EstadoConfianza::CONFIABLE, EstadoVigencia::PUEDE_ESTAR_DESACTUALIZADO);
        $resultado = SelectorPrecioVigente::seleccionar($candidato);
        expect($resultado)->toBe(ResultadoSelector::MOSTRAR_CON_ADVERTENCIA);
    });

})->group('domain');
