<?php

declare(strict_types=1);

namespace App\Domains\Precios\Application\Handlers;

use App\Domains\Compartido\Domain\ValueObjects\Precio;
use App\Domains\Precios\Application\Commands\RegistrarReportePrecioCommand;
use App\Domains\Precios\Domain\Entities\RegistroPrecio;
use App\Domains\Precios\Domain\Exceptions\LimiteAportesExcedidoException;
use App\Domains\Precios\Domain\Repositories\RegistroPrecioRepository;
use App\Domains\Precios\Domain\Repositories\ResolutorPresentaciones;
use App\Domains\Precios\Domain\Services\LimiteAportes;
use App\Domains\Precios\Domain\ValueObjects\ResultadoLimite;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use InvalidArgumentException;

/**
 * Caso de uso: registrar una observación de precio (RF-08, RF-09).
 *
 * Todo ocurre en una transacción: el INSERT de la observación y los jobs del outbox
 * (listeners en cola) se confirman juntos o no se confirman (ADR-0003).
 */
final class RegistrarReportePrecioHandler
{
    /** El "día" de RN-08 es el día calendario local del proyecto (Quindío). */
    private const ZONA_HORARIA = 'America/Bogota';

    public function __construct(
        private readonly RegistroPrecioRepository $repositorio,
        private readonly ResolutorPresentaciones  $presentaciones,
        private readonly Dispatcher               $eventos,
        private readonly ConnectionInterface      $db,
    ) {}

    public function handle(RegistrarReportePrecioCommand $cmd): ResultadoRegistro
    {
        $existente = $this->repositorio->idPorClaveIdempotencia($cmd->claveIdempotencia);
        if ($existente !== null) {
            return new ResultadoRegistro($existente, esReintento: true);
        }

        try {
            return $this->db->transaction(fn (): ResultadoRegistro => $this->registrar($cmd));
        } catch (UniqueConstraintViolationException) {
            // Dos envíos concurrentes con la misma clave: gana el primero.
            $id = $this->repositorio->idPorClaveIdempotencia($cmd->claveIdempotencia);
            if ($id === null) {
                throw new InvalidArgumentException('No fue posible registrar el reporte');
            }

            return new ResultadoRegistro($id, esReintento: true);
        }
    }

    private function registrar(RegistrarReportePrecioCommand $cmd): ResultadoRegistro
    {
        $productoId = $this->presentaciones->productoDe($cmd->presentacionId);
        if ($productoId === null) {
            throw new InvalidArgumentException('La presentación no existe en el catálogo');
        }

        $this->verificarLimiteHorario($cmd->reportadoPor);
        $this->reemplazarReporteDelDia($cmd);

        $registro = RegistroPrecio::crear(
            presentacionId:    $cmd->presentacionId,
            productoId:        $productoId,
            establecimientoId: $cmd->establecimientoId,
            precio:            Precio::de($cmd->precio, 'COP'),
            observadoEn:       $cmd->observadoEn,
            reportadoPor:      $cmd->reportadoPor,
            claveIdempotencia: $cmd->claveIdempotencia,
            fuente:            $cmd->fuente,
        );

        $this->repositorio->save($registro);

        foreach ($registro->pullDomainEvents() as $evento) {
            $this->eventos->dispatch($evento);
        }

        return new ResultadoRegistro($registro->id(), esReintento: false);
    }

    /** RN-08: máximo 30 reportes por hora por reportante (incluido el actual). */
    private function verificarLimiteHorario(string $reportadoPor): void
    {
        $previos = $this->repositorio->contarAportesDesde($reportadoPor, new DateTimeImmutable('-1 hour'));

        if (LimiteAportes::verificar($previos + 1) === ResultadoLimite::LIMITE_EXCEDIDO) {
            throw new LimiteAportesExcedidoException();
        }
    }

    /** RN-08: por presentación, establecimiento y día cuenta solo el último reporte del usuario. */
    private function reemplazarReporteDelDia(RegistrarReportePrecioCommand $cmd): void
    {
        $local  = $cmd->observadoEn->setTimezone(new DateTimeZone(self::ZONA_HORARIA));
        $inicio = $local->setTime(0, 0);
        $fin    = $local->setTime(23, 59, 59);

        $yaExisteHoy = $this->repositorio->existeAporteDelDia(
            $cmd->reportadoPor, $cmd->presentacionId, $cmd->establecimientoId, $inicio, $fin,
        );

        if (LimiteAportes::verificarDuplicado($yaExisteHoy) === ResultadoLimite::REEMPLAZAR) {
            $this->repositorio->retirarAportesDelDia(
                $cmd->reportadoPor, $cmd->presentacionId, $cmd->establecimientoId, $inicio, $fin,
            );
        }
    }
}
