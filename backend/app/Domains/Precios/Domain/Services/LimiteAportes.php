<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Services;

use App\Domains\Precios\Domain\ValueObjects\ResultadoLimite;

final class LimiteAportes
{
    private const MAX_POR_HORA = 30;

    public static function verificar(int $aportesUltimaHora): ResultadoLimite
    {
        return $aportesUltimaHora <= self::MAX_POR_HORA
            ? ResultadoLimite::PERMITIDO
            : ResultadoLimite::LIMITE_EXCEDIDO;
    }

    public static function verificarDuplicado(bool $yaExisteHoy): ResultadoLimite
    {
        return $yaExisteHoy
            ? ResultadoLimite::REEMPLAZAR
            : ResultadoLimite::PERMITIDO;
    }
}
