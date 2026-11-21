<?php

declare(strict_types=1);

use App\Domains\Comercial\Domain\Entities\PerfilComercial;
use App\Domains\Comercial\Domain\Exceptions\PublicacionNoAutorizadaException;
use App\Domains\Comercial\Domain\Services\PoliticaPublicacion;
use App\Domains\Comercial\Domain\ValueObjects\EstadoPerfil;

/** RN-15: solo un perfil VERIFICADO publica, y solo sobre su propio establecimiento (ADR-0022). */
describe('U-15 PoliticaPublicacion y ciclo del perfil', function () {

    $pendiente = fn (): PerfilComercial => PerfilComercial::solicitar('perfil-1', 'tienda-a');

    it('un perfil nace PENDIENTE', function () use ($pendiente) {
        expect($pendiente()->estado)->toBe(EstadoPerfil::PENDIENTE);
    });

    it('verificar devuelve un perfil nuevo VERIFICADO sin mutar el original', function () use ($pendiente) {
        $original = $pendiente();
        $verificado = $original->verificar();

        expect($verificado->estado)->toBe(EstadoPerfil::VERIFICADO)
            ->and($original->estado)->toBe(EstadoPerfil::PENDIENTE)
            ->and($verificado->establecimientoId)->toBe('tienda-a');
    });

    it('no se puede verificar un perfil revocado', function () use ($pendiente) {
        $pendiente()->verificar()->revocar()->verificar();
    })->throws(DomainException::class);

    it('autoriza a un perfil verificado sobre su establecimiento', function () use ($pendiente) {
        PoliticaPublicacion::autorizar($pendiente()->verificar(), 'tienda-a');
        expect(true)->toBeTrue();
    });

    it('rechaza a un perfil pendiente', function () use ($pendiente) {
        PoliticaPublicacion::autorizar($pendiente(), 'tienda-a');
    })->throws(PublicacionNoAutorizadaException::class, 'El perfil comercial aún no está verificado.');

    it('rechaza a un perfil revocado', function () use ($pendiente) {
        PoliticaPublicacion::autorizar($pendiente()->verificar()->revocar(), 'tienda-a');
    })->throws(PublicacionNoAutorizadaException::class, 'El perfil comercial fue revocado.');

    it('rechaza publicar sobre otro establecimiento', function () use ($pendiente) {
        PoliticaPublicacion::autorizar($pendiente()->verificar(), 'tienda-b');
    })->throws(PublicacionNoAutorizadaException::class, 'El perfil solo puede publicar sobre su propio establecimiento.');
});
