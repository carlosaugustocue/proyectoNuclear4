<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Application;

use App\Domains\Confianza\Application\AplicarDecisionModeracion;
use App\Domains\Confianza\Domain\ValueObjects\EstadoConfianza;
use App\Domains\Moderacion\Domain\Exceptions\CasoNoEncontradoException;
use App\Domains\Moderacion\Domain\Exceptions\DecisionNoAplicableException;
use App\Domains\Moderacion\Domain\Repositories\CasosModeracion;
use App\Domains\Moderacion\Domain\ValueObjects\AccionModeracion;
use App\Domains\Moderacion\Domain\ValueObjects\CasoModeracion;
use Illuminate\Database\ConnectionInterface;

/**
 * RF-20 / RNF-11: un moderador resuelve un caso. Confirmar o invalidar cambia el estado de la
 * observación a través de Confianza (interfaz pública); toda decisión queda en la bitácora.
 */
final class ResolverCaso
{
    public function __construct(
        private readonly CasosModeracion           $casos,
        private readonly AplicarDecisionModeracion $confianza,
        private readonly ConnectionInterface       $db,
    ) {}

    public function resolver(string $casoId, AccionModeracion $accion, string $justificacion, string $moderador): CasoModeracion
    {
        $caso = $this->casos->obtener($casoId) ?? throw new CasoNoEncontradoException('El caso no existe.');

        if (! $caso->estaAbierto()) {
            throw new DecisionNoAplicableException('El caso ya fue resuelto.');
        }
        if ($accion === AccionModeracion::SUSPENDER_CUENTA) {
            throw new DecisionNoAplicableException('Suspender cuentas estará disponible con el contexto Identidad (cuentas de usuario).');
        }
        if ($accion->actuaSobreObservacion() && ! $caso->tieneObservacion()) {
            throw new DecisionNoAplicableException('Este caso no corresponde a una observación de precio: solo admite CERRAR_SIN_CAMBIOS.');
        }

        $this->db->transaction(function () use ($caso, $accion, $justificacion, $moderador): void {
            if ($accion->actuaSobreObservacion()) {
                $estado = $accion === AccionModeracion::CONFIRMAR_OBSERVACION ? EstadoConfianza::CONFIABLE : EstadoConfianza::NO_CONFIABLE;
                $this->confianza->aplicar($caso->observacionId, $estado);
            }
            $this->casos->resolver($caso->id, $accion, $justificacion, $moderador);
        });

        return $this->casos->obtener($casoId) ?? $caso;
    }
}
