<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Domain\Repositories;

use App\Domains\Moderacion\Domain\ValueObjects\MotivoMarca;

interface MarcasObservacion
{
    /** @return bool false si esa cuenta ya había marcado la observación */
    public function registrar(string $observacionId, string $cuentaId, MotivoMarca $motivo, ?string $comentario): bool;
}
