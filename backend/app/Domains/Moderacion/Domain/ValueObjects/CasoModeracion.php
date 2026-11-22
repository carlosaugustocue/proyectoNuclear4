<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Domain\ValueObjects;

final class CasoModeracion
{
    /** Orígenes cuyo observacionId es una observación de precio real (no una referencia). */
    private const ORIGENES_CON_OBSERVACION = ['ANOMALIA_AUTOMATICA', 'MARCA_USUARIO'];

    public function __construct(
        public readonly string $id,
        public readonly string $observacionId,
        public readonly string $origen,
        public readonly string $estado,
        public readonly ?string $detalle,
        public readonly string $abiertoEn,
    ) {}

    public function estaAbierto(): bool
    {
        return $this->estado === 'ABIERTO';
    }

    public function tieneObservacion(): bool
    {
        return in_array($this->origen, self::ORIGENES_CON_OBSERVACION, true);
    }
}
