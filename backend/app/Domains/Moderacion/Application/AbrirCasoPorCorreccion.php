<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Application;

use App\Domains\Moderacion\Domain\Repositories\CasosModeracion;
use Ramsey\Uuid\Uuid;

/**
 * RF-34: un comercio pide corregir información sobre su establecimiento (guía §12, equidad entre
 * comercios). La solicitud entra a la misma cola que las anomalías; un moderador decide.
 */
final class AbrirCasoPorCorreccion
{
    public const ORIGEN = 'CORRECCION_COMERCIO';

    public function __construct(private readonly CasosModeracion $casos) {}

    /** @return string referencia del caso abierto */
    public function abrir(string $establecimientoId, string $detalle): string
    {
        $referencia = Uuid::uuid7()->toString();
        $this->casos->abrir($referencia, self::ORIGEN, "Establecimiento {$establecimientoId}: {$detalle}");

        return $referencia;
    }
}
