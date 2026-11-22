<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Domain\Ports;

/**
 * Puerto de Moderación para RF-20 «suspender cuenta». Lo implementa Identidad (ADR-0025), de modo
 * que Moderación no conoce cuentas ni sesiones: solo pide suspender al autor de una observación.
 */
interface SuspensionDeCuentas
{
    /**
     * @return bool false si la observación es anónima (no hay cuenta que suspender)
     * @throws \DomainException si el autor no se puede suspender (p. ej., es moderador)
     */
    public function suspenderAutorDe(string $observacionId): bool;
}
