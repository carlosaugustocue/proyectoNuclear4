<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Domain\Services;

use App\Domains\Confianza\Domain\ValueObjects\EstadoConfianza;
use App\Domains\Confianza\Domain\ValueObjects\EstadoVigencia;
use App\Domains\Confianza\Domain\ValueObjects\ObservacionVigente;
use App\Domains\Confianza\Domain\ValueObjects\PermisoAporte;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DateTimeImmutable;

/**
 * RN-07 + RN-09: ¿la observación nueva reemplaza al precio vigente publicado?
 *
 * Una cuenta sin reputación no puede, por sí sola, cambiar un precio vigente y
 * corroborado; necesita que otro reportante independiente la confirme (RN-05).
 */
final class PoliticaReemplazoVigente
{
    /** Diferencia por debajo de la cual el cambio se considera consistente (misma tolerancia que RN-05). */
    private const TOLERANCIA = '0.05';

    public static function debeReemplazar(
        ?ObservacionVigente $actual,
        string $montoNuevo,
        EstadoConfianza $estadoNuevo,
        PermisoAporte $permiso,
        DateTimeImmutable $observadoEnNuevo,
    ): bool {
        if ($permiso === PermisoAporte::RECHAZADO) {
            return false;
        }
        if ($actual === null) {
            return true;
        }
        if ($observadoEnNuevo < $actual->observadoEn) {
            return false; // una sincronización offline tardía no pisa un dato más reciente
        }
        if ($estadoNuevo === EstadoConfianza::CONFIABLE || $permiso === PermisoAporte::PUEDE_ACTUALIZAR_VIGENTE) {
            return true;
        }

        $actualProtegido = $actual->estado === EstadoConfianza::CONFIABLE
            && $actual->vigencia !== EstadoVigencia::HISTORIAL;

        return ! $actualProtegido || self::esConsistente($actual->monto, $montoNuevo);
    }

    private static function esConsistente(string $montoActual, string $montoNuevo): bool
    {
        $actual = BigDecimal::of($montoActual);
        if ($actual->isZero()) {
            return false;
        }

        $diferencia = BigDecimal::of($montoNuevo)->minus($actual)->abs()->dividedBy($actual, 6, RoundingMode::HALF_UP);

        return $diferencia->isLessThanOrEqualTo(self::TOLERANCIA);
    }
}
