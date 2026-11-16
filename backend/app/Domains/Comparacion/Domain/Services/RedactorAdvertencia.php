<?php

declare(strict_types=1);

namespace App\Domains\Comparacion\Domain\Services;

use App\Domains\Confianza\Domain\ValueObjects\EstadoConfianza;
use App\Domains\Confianza\Domain\ValueObjects\EstadoVigencia;

/** RF-16 / RN-09: advertencia comprensible para el consumidor (ISO/IEC 25012 — comprensibilidad). */
final class RedactorAdvertencia
{
    private const POR_VERIFICAR = 'Precio en verificación: difiere mucho de otros reportes de este producto';

    public static function redactar(EstadoVigencia $vigencia, EstadoConfianza $confianza, int $dias): ?string
    {
        if ($confianza === EstadoConfianza::NO_CONFIABLE) {
            return 'Precio marcado como no confiable';
        }

        $partes = [];

        if ($vigencia === EstadoVigencia::HISTORIAL) {
            $partes[] = sprintf('Precio histórico de hace %s; ya no se considera vigente', self::dias($dias));
        } elseif ($vigencia === EstadoVigencia::PUEDE_ESTAR_DESACTUALIZADO) {
            $partes[] = sprintf('Precio reportado hace %s; puede estar desactualizado', self::dias($dias));
        }

        if ($confianza === EstadoConfianza::POR_VERIFICAR) {
            $partes[] = self::POR_VERIFICAR;
        }

        return $partes === [] ? null : implode('. ', $partes);
    }

    private static function dias(int $dias): string
    {
        return $dias === 1 ? '1 día' : "{$dias} días";
    }
}
