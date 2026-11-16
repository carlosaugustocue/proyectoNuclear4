<?php

declare(strict_types=1);

use App\Domains\Confianza\Domain\Services\EvaluadorConfianza;
use App\Domains\Confianza\Domain\ValueObjects\EstadoConfianza;

/*
 * Combina RN-06 (anomalía) con RN-05 (corroboración) en el estado publicado (ADR-0007).
 * Una anomalía prevalece: un grupo coordinado no puede "corroborar" un precio atípico.
 */
describe('EvaluadorConfianza (RN-05 + RN-06)', function () {
    it('anomalía + corroborado → POR_VERIFICAR (la anomalía prevalece, EC-03)', function () {
        expect(EvaluadorConfianza::combinar(EstadoConfianza::POR_VERIFICAR, EstadoConfianza::CONFIABLE))
            ->toBe(EstadoConfianza::POR_VERIFICAR);
    });

    it('sin anomalía y corroborado → CONFIABLE', function () {
        expect(EvaluadorConfianza::combinar(EstadoConfianza::CONFIABLE, EstadoConfianza::CONFIABLE))
            ->toBe(EstadoConfianza::CONFIABLE);
    });

    it('sin anomalía y sin corroborar → NO_CORROBORADO', function () {
        expect(EvaluadorConfianza::combinar(EstadoConfianza::CONFIABLE, EstadoConfianza::NO_CORROBORADO))
            ->toBe(EstadoConfianza::NO_CORROBORADO);
    });
})->group('domain');
