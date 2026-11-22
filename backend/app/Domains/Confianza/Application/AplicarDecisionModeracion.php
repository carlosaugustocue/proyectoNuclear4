<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Application;

use App\Domains\Confianza\Domain\Events\ConfianzaEvaluada;
use App\Domains\Confianza\Domain\Repositories\EvaluacionesRepositorio;
use App\Domains\Confianza\Domain\ValueObjects\EstadoConfianza;
use App\Domains\Confianza\Domain\ValueObjects\PermisoAporte;
use App\Domains\Precios\Domain\Repositories\ObservacionesLectura;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

/**
 * Interfaz pública de Confianza para Moderación (RF-20). Un moderador confirma o invalida una
 * observación: Confianza cambia su estado y vuelve a publicar ConfianzaEvaluada, así la
 * proyección de Comparación se actualiza por el mismo flujo (ADR-0003). Moderación nunca
 * escribe el modelo de lectura (AR-01).
 */
final class AplicarDecisionModeracion
{
    public function __construct(
        private readonly ObservacionesLectura    $observaciones,
        private readonly EvaluacionesRepositorio $evaluaciones,
        private readonly Dispatcher              $eventos,
    ) {}

    /** @return bool false si la observación no existe (p. ej., fue retirada) */
    public function aplicar(string $observacionId, EstadoConfianza $estado): bool
    {
        if (! in_array($estado, [EstadoConfianza::CONFIABLE, EstadoConfianza::NO_CONFIABLE], true)) {
            throw new InvalidArgumentException('Un moderador solo puede confirmar (CONFIABLE) o invalidar (NO_CONFIABLE).');
        }

        $obs = $this->observaciones->detalle($observacionId);
        if ($obs === null) {
            return false;
        }

        $anterior       = $this->evaluaciones->obtener($observacionId);
        $confirmaciones = $anterior['confirmaciones'] ?? 0;
        // Una confirmación del moderador equivale a corroboración: el precio puede reemplazar al vigente.
        $permiso = $estado === EstadoConfianza::CONFIABLE ? PermisoAporte::PUEDE_ACTUALIZAR_VIGENTE->name : PermisoAporte::RECHAZADO->name;

        $this->evaluaciones->guardar($observacionId, $obs->reportadoPor, $estado->name, $confirmaciones, $permiso);
        if ($estado === EstadoConfianza::CONFIABLE) {
            $this->evaluaciones->marcarConfirmadas([$observacionId]); // suma reputación (RN-07)
        }

        $this->eventos->dispatch(new ConfianzaEvaluada(
            observacionId:     $obs->id,
            productoId:        $obs->productoId,
            presentacionId:    $obs->presentacionId,
            establecimientoId: $obs->establecimientoId,
            monto:             $obs->monto,
            observadoEn:       $obs->observadoEn,
            fuente:            $obs->fuente,
            estado:            $estado->name,
            confirmaciones:    $confirmaciones,
            permiso:           $permiso,
        ));

        return true;
    }
}
