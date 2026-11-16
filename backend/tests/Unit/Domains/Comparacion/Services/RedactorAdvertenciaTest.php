<?php

declare(strict_types=1);

use App\Domains\Comparacion\Domain\Services\RedactorAdvertencia;
use App\Domains\Confianza\Domain\ValueObjects\EstadoConfianza;
use App\Domains\Confianza\Domain\ValueObjects\EstadoVigencia;

/*
 * RF-16 / RN-09: toda observación que no sea vigente y confiable se muestra con una
 * advertencia explícita y comprensible (ISO/IEC 25012 — comprensibilidad).
 */
describe('RedactorAdvertencia (RN-04, RN-09)', function () {
    it('vigente sin problemas → sin advertencia', function (EstadoConfianza $estado) {
        expect(RedactorAdvertencia::redactar(EstadoVigencia::VIGENTE, $estado, 1))->toBeNull();
    })->with([EstadoConfianza::CONFIABLE, EstadoConfianza::NO_CORROBORADO]);

    it('entre 1x y 2x la vigencia → "puede estar desactualizado" con los días', function () {
        expect(RedactorAdvertencia::redactar(EstadoVigencia::PUEDE_ESTAR_DESACTUALIZADO, EstadoConfianza::CONFIABLE, 9))
            ->toBe('Precio reportado hace 9 días; puede estar desactualizado');
    });

    it('usa singular para un día', function () {
        expect(RedactorAdvertencia::redactar(EstadoVigencia::PUEDE_ESTAR_DESACTUALIZADO, EstadoConfianza::CONFIABLE, 1))
            ->toBe('Precio reportado hace 1 día; puede estar desactualizado');
    });

    it('POR_VERIFICAR → advierte que difiere de otros reportes', function () {
        expect(RedactorAdvertencia::redactar(EstadoVigencia::VIGENTE, EstadoConfianza::POR_VERIFICAR, 0))
            ->toBe('Precio en verificación: difiere mucho de otros reportes de este producto');
    });

    it('historial → precio no vigente', function () {
        expect(RedactorAdvertencia::redactar(EstadoVigencia::HISTORIAL, EstadoConfianza::CONFIABLE, 20))
            ->toBe('Precio histórico de hace 20 días; ya no se considera vigente');
    });

    it('NO_CONFIABLE → no confiable por moderación (prevalece sobre la vigencia)', function () {
        expect(RedactorAdvertencia::redactar(EstadoVigencia::VIGENTE, EstadoConfianza::NO_CONFIABLE, 0))
            ->toBe('Precio marcado como no confiable');
    });

    it('desactualizado y por verificar a la vez → combina ambas advertencias', function () {
        expect(RedactorAdvertencia::redactar(EstadoVigencia::PUEDE_ESTAR_DESACTUALIZADO, EstadoConfianza::POR_VERIFICAR, 10))
            ->toBe('Precio reportado hace 10 días; puede estar desactualizado. Precio en verificación: difiere mucho de otros reportes de este producto');
    });
})->group('domain');
