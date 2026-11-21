<?php

declare(strict_types=1);

use App\Domains\Comercial\Domain\Services\ValidadorPromocion;
use App\Domains\Comercial\Domain\Services\VisibilidadPromocion;
use App\Domains\Comercial\Domain\ValueObjects\EstadoPerfil;

/** RN-16: visible si el perfil está verificado, no fue retirada y ahora ∈ [inicio, fin]. */
describe('U-16 VisibilidadPromocion', function () {

    $inicio = new DateTimeImmutable('2026-11-21T00:00:00-05:00');
    $promo = fn () => ValidadorPromocion::validar('DESCUENTO_PORCENTUAL', '10', null, null, $inicio, $inicio->modify('+7 days'));

    it('es visible dentro de la vigencia con perfil verificado', function () use ($promo, $inicio) {
        expect(VisibilidadPromocion::esVisible($promo(), EstadoPerfil::VERIFICADO, null, $inicio->modify('+3 days')))->toBeTrue();
    });

    it('los extremos de la vigencia cuentan como vigentes', function () use ($promo, $inicio) {
        expect(VisibilidadPromocion::esVisible($promo(), EstadoPerfil::VERIFICADO, null, $inicio))->toBeTrue()
            ->and(VisibilidadPromocion::esVisible($promo(), EstadoPerfil::VERIFICADO, null, $inicio->modify('+7 days')))->toBeTrue();
    });

    it('no es visible antes de empezar ni después de vencer', function () use ($promo, $inicio) {
        expect(VisibilidadPromocion::esVisible($promo(), EstadoPerfil::VERIFICADO, null, $inicio->modify('-1 second')))->toBeFalse()
            ->and(VisibilidadPromocion::esVisible($promo(), EstadoPerfil::VERIFICADO, null, $inicio->modify('+8 days')))->toBeFalse();
    });

    it('no es visible si el perfil está revocado o pendiente', function (string $estado) use ($promo, $inicio) {
        expect(VisibilidadPromocion::esVisible($promo(), EstadoPerfil::from($estado), null, $inicio->modify('+1 day')))->toBeFalse();
    })->with(['REVOCADO', 'PENDIENTE']);

    it('no es visible desde que se retira', function () use ($promo, $inicio) {
        $retirada = $inicio->modify('+2 days');
        expect(VisibilidadPromocion::esVisible($promo(), EstadoPerfil::VERIFICADO, $retirada, $inicio->modify('+1 day')))->toBeTrue()
            ->and(VisibilidadPromocion::esVisible($promo(), EstadoPerfil::VERIFICADO, $retirada, $inicio->modify('+3 days')))->toBeFalse();
    });
});
