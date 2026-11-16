<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Application;

use App\Domains\Confianza\Domain\Events\ConfianzaEvaluada;
use App\Domains\Confianza\Domain\ValueObjects\EstadoConfianza;
use App\Domains\Moderacion\Domain\Repositories\CasosModeracion;

/**
 * RN-06 / EC-03: una observación POR_VERIFICAR entra a la cola de moderación.
 * No se elimina: se muestra con advertencia mientras un moderador decide (ADR-0007).
 */
final class AbrirCasoPorAnomalia
{
    public const ORIGEN = 'ANOMALIA_AUTOMATICA';

    public function __construct(private readonly CasosModeracion $casos) {}

    public function siCorresponde(ConfianzaEvaluada $e): bool
    {
        if ($e->estado !== EstadoConfianza::POR_VERIFICAR->name) {
            return false;
        }

        $this->casos->abrir($e->observacionId, self::ORIGEN, "Precio {$e->monto} atípico frente a otros reportes de la presentación");

        return true;
    }
}
