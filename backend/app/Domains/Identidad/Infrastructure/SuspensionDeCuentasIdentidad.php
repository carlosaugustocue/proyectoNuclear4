<?php

declare(strict_types=1);

namespace App\Domains\Identidad\Infrastructure;

use App\Domains\Identidad\Application\GestionarCuentas;
use App\Domains\Moderacion\Domain\Ports\SuspensionDeCuentas;
use App\Domains\Precios\Domain\Repositories\ObservacionesLectura;

/** Adaptador del puerto de Moderación: el autor sale de la interfaz pública de Precios. */
final class SuspensionDeCuentasIdentidad implements SuspensionDeCuentas
{
    public function __construct(
        private readonly ObservacionesLectura $observaciones,
        private readonly GestionarCuentas     $cuentas,
    ) {}

    public function suspenderAutorDe(string $observacionId): bool
    {
        $autor = $this->observaciones->detalle($observacionId)?->reportadoPor;

        // Un reporte anónimo (seudónimo «anon-…») o de una cuenta ya suprimida no tiene a quién suspender.
        return $autor !== null && $this->cuentas->suspender($autor);
    }
}
