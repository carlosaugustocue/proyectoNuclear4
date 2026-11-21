<?php

declare(strict_types=1);

use App\Domains\Comercial\Domain\Exceptions\PromocionInvalidaException;
use App\Domains\Comercial\Domain\Services\ValidadorPromocion;
use App\Domains\Comercial\Domain\ValueObjects\TipoPromocion;

/** RN-14 ⚑: descuento 1–90 %, precio especial > 0, pague X lleve Y con lleve > pague ≥ 1, vigencia ≤ 31 días. */
describe('U-14 ValidadorPromocion', function () {

    $inicio = new DateTimeImmutable('2026-11-21T00:00:00-05:00');
    $errores = function (callable $f): array {
        try {
            $f();
        } catch (PromocionInvalidaException $e) {
            return $e->errores;
        }
        throw new RuntimeException('Se esperaba PromocionInvalidaException');
    };

    it('acepta un descuento porcentual válido y lo describe', function () use ($inicio) {
        $p = ValidadorPromocion::validar('DESCUENTO_PORCENTUAL', '20', null, null, $inicio, $inicio->modify('+7 days'));

        expect($p->tipo)->toBe(TipoPromocion::DESCUENTO_PORCENTUAL)
            ->and($p->valor)->toBe('20')
            ->and($p->descripcion())->toBe('20 % de descuento');
    });

    it('acepta un precio especial y lo describe en pesos', function () use ($inicio) {
        $p = ValidadorPromocion::validar('PRECIO_ESPECIAL', '2500', null, null, $inicio, $inicio->modify('+1 day'));

        expect($p->descripcion())->toBe('Precio especial $2.500');
    });

    it('acepta pague X lleve Y y lo describe', function () use ($inicio) {
        $p = ValidadorPromocion::validar('PAGUE_X_LLEVE_Y', null, 2, 3, $inicio, $inicio->modify('+1 day'));

        expect($p->valor)->toBeNull()
            ->and($p->descripcion())->toBe('Pague 2, lleve 3');
    });

    it('rechaza descuentos fuera de 1 % a 90 %', function (string $valor) use ($inicio, $errores) {
        expect($errores(fn () => ValidadorPromocion::validar('DESCUENTO_PORCENTUAL', $valor, null, null, $inicio, $inicio->modify('+1 day'))))
            ->toContain('El descuento debe estar entre 1 % y 90 %.');
    })->with(['0', '0.5', '91', '95', '-10']);

    it('rechaza un precio especial que no es positivo', function () use ($inicio, $errores) {
        expect($errores(fn () => ValidadorPromocion::validar('PRECIO_ESPECIAL', '0', null, null, $inicio, $inicio->modify('+1 day'))))
            ->toContain('El precio especial debe ser mayor que cero.');
    });

    it('rechaza pague X lleve Y cuando no se lleva más de lo que se paga', function (int $pague, int $lleve) use ($inicio, $errores) {
        expect($errores(fn () => ValidadorPromocion::validar('PAGUE_X_LLEVE_Y', null, $pague, $lleve, $inicio, $inicio->modify('+1 day'))))
            ->toContain('En «pague X lleve Y», Y debe ser mayor que X y X al menos 1.');
    })->with([[3, 3], [3, 2], [0, 1]]);

    it('rechaza un fin anterior o igual al inicio', function () use ($inicio, $errores) {
        expect($errores(fn () => ValidadorPromocion::validar('DESCUENTO_PORCENTUAL', '10', null, null, $inicio, $inicio)))
            ->toContain('La fecha de fin debe ser posterior a la de inicio.');
    });

    it('acepta exactamente 31 días y rechaza más', function () use ($inicio, $errores) {
        expect(ValidadorPromocion::validar('DESCUENTO_PORCENTUAL', '10', null, null, $inicio, $inicio->modify('+31 days'))->tipo)
            ->toBe(TipoPromocion::DESCUENTO_PORCENTUAL)
            ->and($errores(fn () => ValidadorPromocion::validar('DESCUENTO_PORCENTUAL', '10', null, null, $inicio, $inicio->modify('+31 days +1 second'))))
            ->toContain('Una promoción no puede durar más de 31 días.');
    });

    it('rechaza un tipo desconocido', function () use ($inicio, $errores) {
        expect($errores(fn () => ValidadorPromocion::validar('DOS_POR_UNO', '10', null, null, $inicio, $inicio->modify('+1 day'))))
            ->toContain('Tipo de promoción desconocido: DOS_POR_UNO.');
    });

    it('reúne todos los errores en una sola respuesta', function () use ($inicio, $errores) {
        expect($errores(fn () => ValidadorPromocion::validar('DESCUENTO_PORCENTUAL', '95', null, null, $inicio, $inicio->modify('-1 day'))))
            ->toHaveCount(2);
    });
});
