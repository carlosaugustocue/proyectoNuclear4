<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Application;

use App\Domains\Confianza\Domain\Events\ConfianzaEvaluada;
use App\Domains\Confianza\Domain\Repositories\EvaluacionesRepositorio;
use App\Domains\Confianza\Domain\Services\Corroboracion;
use App\Domains\Confianza\Domain\Services\DetectorAnomalias;
use App\Domains\Confianza\Domain\Services\EvaluadorConfianza;
use App\Domains\Confianza\Domain\Services\PoliticaReputacion;
use App\Domains\Confianza\Domain\ValueObjects\AporteCorroboracion;
use App\Domains\Confianza\Domain\ValueObjects\CuentaContribuidora;
use App\Domains\Confianza\Domain\ValueObjects\EstadoConfianza;
use App\Domains\Precios\Domain\Events\PrecioRegistrado;
use App\Domains\Precios\Domain\Repositories\ObservacionesLectura;
use App\Domains\Precios\Domain\ValueObjects\ObservacionPrecio;
use DateTimeImmutable;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Caso de uso del contexto Confianza (ADR-0007): evalúa cada observación nueva con
 * RN-05 (corroboración), RN-06 (anomalía) y RN-07 (reputación) y publica el resultado.
 */
final class EvaluarConfianzaObservacion
{
    /** n mínimo para el puntaje z modificado (RN-06). */
    private const N_MINIMO_MAD = 5;
    /** Ventana de "observaciones vigentes" de la presentación: 2× la vigencia de abarrotes. */
    private const DIAS_REFERENCIA = 14;
    private const HISTORIAL_ESTABLECIMIENTO = 20;
    private const VENTANA_CORROBORACION_HORAS = 48;

    public function __construct(
        private readonly ObservacionesLectura    $observaciones,
        private readonly EvaluacionesRepositorio $evaluaciones,
        private readonly Dispatcher              $eventos,
    ) {}

    public function evaluar(PrecioRegistrado $e): ConfianzaEvaluada
    {
        $anomalia      = DetectorAnomalias::evaluar($e->monto, $this->referenciaParaAnomalias($e));
        $corroborantes = Corroboracion::corroborantes($this->aportesRecientes($e), $e->monto);
        $corroboracion = Corroboracion::evaluar($corroborantes, $e->monto);
        $estado        = EvaluadorConfianza::combinar($anomalia, $corroboracion);
        $permiso       = PoliticaReputacion::evaluar($this->cuentaDe($e->reportadoPor));
        $confirmaciones = max(0, count($corroborantes) - 1);

        $this->evaluaciones->guardar($e->registroId, $e->reportadoPor, $estado->name, $confirmaciones, $permiso->name);

        // Solo una observación CONFIABLE (corroborada y sin anomalía) suma reputación.
        if ($estado === EstadoConfianza::CONFIABLE) {
            $this->evaluaciones->marcarConfirmadas(array_values(array_filter(
                array_map(static fn (AporteCorroboracion $a): ?string => $a->observacionId, $corroborantes),
            )));
        }

        $evaluada = new ConfianzaEvaluada(
            observacionId:     $e->registroId,
            productoId:        $e->productoId,
            presentacionId:    $e->presentacionId,
            establecimientoId: $e->establecimientoId,
            monto:             $e->monto,
            observadoEn:       $e->observadoEn,
            fuente:            $e->fuente,
            estado:            $estado->name,
            confirmaciones:    $confirmaciones,
            permiso:           $permiso->name,
        );

        $this->eventos->dispatch($evaluada);

        return $evaluada;
    }

    /**
     * RN-06: con n ≥ 5 se compara contra los precios vigentes de la presentación en otros
     * establecimientos; con n < 5, contra el historial del mismo establecimiento (±50 %).
     *
     * @return list<string>
     */
    private function referenciaParaAnomalias(PrecioRegistrado $e): array
    {
        $vigentes = $this->observaciones->ultimoPrecioPorEstablecimiento(
            $e->presentacionId, $e->registroId, new DateTimeImmutable('-' . self::DIAS_REFERENCIA . ' days'),
        );

        if (count($vigentes) >= self::N_MINIMO_MAD) {
            return $vigentes;
        }

        return array_slice(
            $this->observaciones->historialEnEstablecimiento(
                $e->presentacionId, $e->establecimientoId, $e->registroId, self::HISTORIAL_ESTABLECIMIENTO,
            ),
            0,
            self::N_MINIMO_MAD - 1, // se mantiene en la rama n < 5 del detector
        );
    }

    /** @return list<AporteCorroboracion> */
    private function aportesRecientes(PrecioRegistrado $e): array
    {
        $recientes = $this->observaciones->recientesEnEstablecimiento(
            $e->presentacionId, $e->establecimientoId,
            new DateTimeImmutable('-' . self::VENTANA_CORROBORACION_HORAS . ' hours'),
        );

        return array_map(static fn (ObservacionPrecio $o): AporteCorroboracion => new AporteCorroboracion(
            $o->reportadoPor, $o->monto, $o->observadoEn, $o->id,
        ), $recientes);
    }

    private function cuentaDe(string $reportadoPor): CuentaContribuidora
    {
        return new CuentaContribuidora(
            id:                 $reportadoPor,
            creadaEn:           $this->observaciones->primerAporteDe($reportadoPor) ?? new DateTimeImmutable(),
            aportesConfirmados: $this->evaluaciones->aportesConfirmadosDe($reportadoPor),
            suspendida:         false, // la suspensión llega con el contexto Identidad
        );
    }
}
