<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Application;

use App\Domains\Confianza\Application\AplicarDecisionModeracion;
use App\Domains\Confianza\Domain\ValueObjects\EstadoConfianza;
use App\Domains\Moderacion\Domain\Exceptions\CasoNoEncontradoException;
use App\Domains\Moderacion\Domain\Exceptions\DecisionNoAplicableException;
use App\Domains\Moderacion\Domain\Ports\SuspensionDeCuentas;
use App\Domains\Moderacion\Domain\Repositories\CasosModeracion;
use App\Domains\Moderacion\Domain\ValueObjects\AccionModeracion;
use App\Domains\Moderacion\Domain\ValueObjects\CasoModeracion;
use Illuminate\Database\ConnectionInterface;

/**
 * RF-20 / RNF-11: un moderador resuelve un caso. Confirmar o invalidar cambia el estado de la
 * observación a través de Confianza (interfaz pública); toda decisión queda en la bitácora.
 * Suspender una cuenta pasa por el puerto SuspensionDeCuentas (lo implementa Identidad) e invalida
 * además la observación que originó el caso.
 */
final class ResolverCaso
{
    public function __construct(
        private readonly CasosModeracion           $casos,
        private readonly AplicarDecisionModeracion $confianza,
        private readonly ConnectionInterface       $db,
        private readonly SuspensionDeCuentas       $suspension,
    ) {}

    public function resolver(string $casoId, AccionModeracion $accion, string $justificacion, string $moderador): CasoModeracion
    {
        $caso = $this->casos->obtener($casoId) ?? throw new CasoNoEncontradoException('El caso no existe.');

        if (! $caso->estaAbierto()) {
            throw new DecisionNoAplicableException('El caso ya fue resuelto.');
        }
        if ($accion->actuaSobreObservacion() && ! $caso->tieneObservacion()) {
            throw new DecisionNoAplicableException('Este caso no corresponde a una observación de precio: solo admite CERRAR_SIN_CAMBIOS.');
        }

        $this->db->transaction(function () use ($caso, $accion, $justificacion, $moderador): void {
            if ($accion === AccionModeracion::SUSPENDER_CUENTA) {
                $this->suspenderAutor($caso->observacionId);
            }
            if ($accion->actuaSobreObservacion()) {
                $estado = $accion === AccionModeracion::CONFIRMAR_OBSERVACION ? EstadoConfianza::CONFIABLE : EstadoConfianza::NO_CONFIABLE;
                $this->confianza->aplicar($caso->observacionId, $estado);
            }
            $this->casos->resolver($caso->id, $accion, $justificacion, $moderador);
        });

        return $this->casos->obtener($casoId) ?? $caso;
    }

    private function suspenderAutor(string $observacionId): void
    {
        try {
            $suspendida = $this->suspension->suspenderAutorDe($observacionId);
        } catch (\DomainException $e) {
            throw new DecisionNoAplicableException($e->getMessage(), 0, $e);
        }
        if (! $suspendida) {
            throw new DecisionNoAplicableException('El reporte es anónimo: no hay una cuenta que suspender.');
        }
    }
}
