<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Infrastructure\Persistence;

use App\Domains\Moderacion\Domain\Repositories\MarcasObservacion;
use App\Domains\Moderacion\Domain\ValueObjects\MotivoMarca;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class PgMarcasObservacion implements MarcasObservacion
{
    public function registrar(string $observacionId, string $cuentaId, MotivoMarca $motivo, ?string $comentario): bool
    {
        return DB::table('marcas_observacion')->insertOrIgnore([
            'id'             => Uuid::uuid7()->toString(),
            'observacion_id' => $observacionId,
            'cuenta_id'      => $cuentaId,
            'motivo'         => $motivo->value,
            'comentario'     => $comentario,
            'creado_en'      => now(),
        ]) === 1;
    }
}
