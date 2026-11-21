<?php

declare(strict_types=1);

namespace App\Domains\Comparacion\Application;

use App\Domains\Catalogo\Application\ConsultarCatalogo;
use App\Domains\Comparacion\Domain\Repositories\ModeloLecturaComparacion;
use App\Domains\Confianza\Domain\Services\PoliticaVigencia;
use App\Domains\Confianza\Domain\Services\SelectorPrecioVigente;
use App\Domains\Confianza\Domain\ValueObjects\CandidatoPrecio;
use App\Domains\Confianza\Domain\ValueObjects\ResultadoSelector;

/**
 * Interfaz pública de Comparación para otros contextos (punto de extensión, ADR-0017).
 * Expone solo precios que la comparación mostraría como vigentes (RN-04, RN-09), ya
 * normalizados por unidad base, sin datos de reportantes (EC-07) ni atributos comerciales
 * (ADR-0009). No modifica ConsultarComparacion: reutiliza el mismo modelo de lectura.
 */
final class ConsultarPreciosVigentes
{
    /** Unidad base del modelo de lectura → unidad del núcleo compartido. */
    private const UNIDAD = ['KG' => 'kg', 'L' => 'L', 'UNIDAD' => 'unidad'];

    public function __construct(
        private readonly ModeloLecturaComparacion $modelo,
        private readonly ConsultarCatalogo        $catalogo,
    ) {}

    /**
     * @param  list<string>          $productoIds
     * @return array<string, string> productoId => unidad base (kg, L, unidad); omite los que no existen
     */
    public function unidadesBase(array $productoIds): array
    {
        $unidades = [];
        foreach ($productoIds as $id) {
            $presentaciones = $this->catalogo->detalle($id)['presentaciones'] ?? null;
            $primera = is_array($presentaciones) ? ($presentaciones[0] ?? null) : null;
            $base = is_array($primera) && is_string($primera['unidadBase'] ?? null) ? $primera['unidadBase'] : null;
            if ($base !== null && isset(self::UNIDAD[$base])) {
                $unidades[$id] = self::UNIDAD[$base];
            }
        }

        return $unidades;
    }

    /**
     * @param  list<string> $productoIds
     * @return list<array{productoId: string, establecimientoId: string, establecimientoNombre: string, precioPorUnidad: string, unidadBase: string}>
     */
    public function vigentes(array $productoIds, ?string $municipio = null): array
    {
        $vigentes = [];
        foreach ($this->modelo->deProductos($productoIds, null, $municipio) as $fila) {
            $vigencia = PoliticaVigencia::evaluar($fila->observadoEn, Estados::categoria($fila->tipoVigencia));
            $candidato = new CandidatoPrecio($fila->precio, Estados::confianza($fila->estadoConfianza), $vigencia);
            if (SelectorPrecioVigente::seleccionar($candidato) === ResultadoSelector::NO_MOSTRAR) {
                continue;
            }

            $vigentes[] = [
                'productoId'            => $fila->productoId,
                'establecimientoId'     => (string) $fila->establecimiento['id'],
                'establecimientoNombre' => (string) $fila->establecimiento['nombre'],
                'precioPorUnidad'       => $fila->precioPorUnidad,
                'unidadBase'            => self::UNIDAD[$fila->unidadBase] ?? $fila->unidadBase,
            ];
        }

        return $vigentes;
    }
}
