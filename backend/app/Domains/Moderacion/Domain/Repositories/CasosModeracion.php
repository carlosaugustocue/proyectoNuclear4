<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Domain\Repositories;

interface CasosModeracion
{
    /** Abre un caso si no existe uno del mismo origen para la observación. */
    public function abrir(string $observacionId, string $origen, string $detalle): void;
}
