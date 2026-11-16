<?php

declare(strict_types=1);

namespace App\Http\Support;

/**
 * Seudónimo estable del reportante mientras el contexto Identidad (JWT) no está
 * implementado. Se deriva con HMAC-SHA256 de la IP y APP_KEY: permite aplicar
 * RN-05 (reportantes independientes) y RN-08 (límites) sin almacenar la IP (R-01).
 */
final class SeudonimoReportante
{
    public static function desde(?string $ip, string $secreto): string
    {
        return 'anon-' . substr(hash_hmac('sha256', $ip ?? 'desconocida', $secreto), 0, 24);
    }
}
