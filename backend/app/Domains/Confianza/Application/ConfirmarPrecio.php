<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Application;

use App\Domains\Confianza\Domain\Exceptions\ConfirmacionNoAplicableException;
use App\Domains\Confianza\Domain\Exceptions\ObservacionNoEncontradaException;
use App\Domains\Confianza\Domain\Ports\PrecioPublicado;
use App\Domains\Confianza\Domain\Repositories\EvaluacionesRepositorio;
use App\Domains\Confianza\Domain\ValueObjects\EstadoConfianza;
use App\Domains\Precios\Application\Commands\RegistrarReportePrecioCommand;
use App\Domains\Precios\Application\Handlers\RegistrarReportePrecioHandler;
use App\Domains\Precios\Application\Handlers\ResultadoRegistro;
use App\Domains\Precios\Domain\Repositories\ObservacionesLectura;
use App\Domains\Precios\Domain\Repositories\RegistroPrecioRepository;
use DateTimeImmutable;

/**
 * RF-10 «sigue igual» (ADR-0026). Confirmar es observar de nuevo el mismo precio: se registra una
 * observación a nombre de quien confirma y pasa por el flujo normal (RN-05 corroboración, RN-08
 * límite, outbox). Así no hay un atajo que salte las reglas de confianza.
 */
final class ConfirmarPrecio
{
    public function __construct(
        private readonly ObservacionesLectura          $observaciones,
        private readonly RegistroPrecioRepository      $registros,
        private readonly EvaluacionesRepositorio       $evaluaciones,
        private readonly RegistrarReportePrecioHandler $registrar,
        private readonly PrecioPublicado               $publicado,
    ) {}

    public function confirmar(string $observacionId, string $cuentaId, string $claveIdempotencia): ResultadoRegistro
    {
        // Un reintento con la misma clave devuelve lo ya registrado aunque el original ya no sea el más reciente.
        $existente = $this->registros->idPorClaveIdempotencia($claveIdempotencia);
        if ($existente !== null) {
            return new ResultadoRegistro($existente, esReintento: true);
        }

        $obs = $this->observaciones->detalle($observacionId)
            ?? throw new ObservacionNoEncontradaException('El precio no existe.');

        if ($obs->reportadoPor === $cuentaId) {
            throw new ConfirmacionNoAplicableException('No puedes confirmar un precio que tú mismo reportaste.');
        }
        if (($this->evaluaciones->obtener($observacionId)['estado'] ?? null) === EstadoConfianza::NO_CONFIABLE->name) {
            throw new ConfirmacionNoAplicableException('Este precio fue invalidado por moderación; si ves otro, repórtalo.');
        }
        if ($this->publicado->observacionPublicada($obs->presentacionId, $obs->establecimientoId) !== $observacionId) {
            throw new ConfirmacionNoAplicableException('Este precio ya no es el que se muestra para esta tienda; actualiza la página o reporta el que ves.');
        }

        return $this->registrar->handle(new RegistrarReportePrecioCommand(
            presentacionId:    $obs->presentacionId,
            establecimientoId: $obs->establecimientoId,
            precio:            $obs->monto,
            observadoEn:       new DateTimeImmutable(),
            reportadoPor:      $cuentaId,
            claveIdempotencia: $claveIdempotencia,
        ));
    }
}
