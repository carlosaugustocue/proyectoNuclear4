<?php

declare(strict_types=1);

namespace App\Domains\Comparacion\Application;

use App\Domains\Catalogo\Application\ConsultarCatalogo;
use App\Domains\Comparacion\Domain\Repositories\ModeloLecturaComparacion;
use App\Domains\Comparacion\Domain\ValueObjects\FilaComparacion;
use App\Domains\Compartido\Domain\Services\CalculadoraPrecioUnitario;
use App\Domains\Compartido\Domain\ValueObjects\Coordenadas;
use App\Domains\Confianza\Domain\Events\ConfianzaEvaluada;
use App\Domains\Confianza\Domain\Services\PoliticaReemplazoVigente;
use App\Domains\Confianza\Domain\Services\PoliticaVigencia;
use App\Domains\Confianza\Domain\ValueObjects\ObservacionVigente;
use App\Domains\Establecimientos\Application\ConsultarDirectorio;
use DateTimeImmutable;

/**
 * Proyección del modelo de lectura (CQRS ligero, ADR-0003). Se ejecuta en el worker
 * al recibir ConfianzaEvaluada; su latencia acota la actualidad del dato (EC-02).
 */
final class ProyectarComparacion
{
    public function __construct(
        private readonly ModeloLecturaComparacion $modelo,
        private readonly ConsultarCatalogo        $catalogo,
        private readonly ConsultarDirectorio      $directorio,
    ) {}

    public function proyectar(ConfianzaEvaluada $e): bool
    {
        $presentacion    = $this->catalogo->presentacion($e->presentacionId);
        $establecimiento = $this->directorio->porId($e->establecimientoId);
        if ($presentacion === null || $establecimiento === null) {
            return false;
        }

        $observadoEn = new DateTimeImmutable($e->observadoEn);
        $actual      = $this->modelo->obtener($e->presentacionId, $e->establecimientoId);

        // Reevaluación de la observación ya publicada (p. ej., decisión de moderación): su fila
        // siempre se actualiza, para que una invalidación la retire y una confirmación quite la advertencia.
        $mismaObservacion = $actual !== null && $actual->observacionId === $e->observacionId;

        if (! $mismaObservacion && ! PoliticaReemplazoVigente::debeReemplazar(
            $actual === null ? null : self::comoVigente($actual),
            $e->monto,
            Estados::confianza($e->estado),
            Estados::permiso($e->permiso),
            $observadoEn,
        )) {
            return false; // RN-07: el precio publicado se mantiene
        }

        $unitario = CalculadoraPrecioUnitario::calcular(
            $e->monto, $presentacion->cantidad, $presentacion->unidad, $presentacion->unidadesPorEmpaque,
        );

        $this->modelo->guardar(new FilaComparacion(
            productoId:      $e->productoId,
            observacionId:   $e->observacionId,
            precio:          $e->monto,
            precioPorUnidad: $unitario->precioPorUnidad,
            unidadBase:      $unitario->unidadBase,
            observadoEn:     $observadoEn,
            fuente:          $e->fuente,
            confirmaciones:  $e->confirmaciones,
            estadoConfianza: $e->estado,
            tipoVigencia:    $presentacion->tipoVigencia,
            establecimiento: [
                'id'        => $establecimiento->id,
                'nombre'    => $establecimiento->nombre,
                'tipo'      => $establecimiento->tipo,
                'barrio'    => $establecimiento->barrio,
                'municipio' => $establecimiento->municipio,
            ],
            presentacion:    $presentacion->toArray(),
        ), Coordenadas::de($establecimiento->lat, $establecimiento->lon));

        return true;
    }

    private static function comoVigente(FilaComparacion $f): ObservacionVigente
    {
        return new ObservacionVigente(
            $f->precio,
            Estados::confianza($f->estadoConfianza),
            PoliticaVigencia::evaluar($f->observadoEn, Estados::categoria($f->tipoVigencia)),
            $f->observadoEn,
        );
    }
}
