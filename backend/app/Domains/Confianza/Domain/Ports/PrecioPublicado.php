<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Domain\Ports;

/**
 * Qué observación se está mostrando para una presentación en un establecimiento. RF-10 solo deja
 * confirmar ese precio: puede no ser el más reciente, porque RN-07 o una invalidación lo mantienen.
 * Lo implementa Comparación con su modelo de lectura (ADR-0026), así Confianza no depende de ella.
 */
interface PrecioPublicado
{
    public function observacionPublicada(string $presentacionId, string $establecimientoId): ?string;
}
