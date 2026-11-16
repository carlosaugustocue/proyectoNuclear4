<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Infrastructure\Persistence;

use App\Domains\Moderacion\Domain\Repositories\CasosModeracion;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class PgCasosModeracion implements CasosModeracion
{
    public function abrir(string $observacionId, string $origen, string $detalle): void
    {
        DB::table('casos_moderacion')->insertOrIgnore([
            'id'             => Uuid::uuid7()->toString(),
            'observacion_id' => $observacionId,
            'origen'         => $origen,
            'estado'         => 'ABIERTO',
            'detalle'        => mb_substr($detalle, 0, 200),
            'creado_en'      => now(),
        ]);
    }
}
