<?php

declare(strict_types=1);

namespace App\Domains\Canasta\Application;

use App\Domains\Canasta\Domain\Ports\FuentePreciosVigentes;
use App\Domains\Canasta\Domain\Services\CalculadoraCanasta;
use App\Domains\Canasta\Domain\Services\OrdenCanasta;
use App\Domains\Canasta\Domain\Services\ValidadorCanasta;
use App\Domains\Canasta\Domain\ValueObjects\ItemCanasta;
use App\Domains\Canasta\Domain\ValueObjects\ItemSolicitado;
use App\Domains\Canasta\Domain\ValueObjects\LineaCanasta;
use App\Domains\Canasta\Domain\ValueObjects\ResultadoEstablecimiento;
use DateTimeImmutable;

/**
 * Caso de uso RF-27 a RF-30: valida la canasta (RN-11), calcula el total por establecimiento
 * (RN-12) y lo ordena por el criterio declarado (RN-13). Sin estado: la canasta no se persiste
 * (ADR-0018). Las promociones nunca entran al total ni al orden (ADR-0019).
 */
final class CompararCanasta
{
    public function __construct(private readonly FuentePreciosVigentes $fuente) {}

    /**
     * @param  list<ItemSolicitado> $solicitados
     * @return array<string, mixed> ResultadoCanasta del contrato
     */
    public function comparar(array $solicitados, string $criterio, ?string $municipio): array
    {
        $ids = array_values(array_unique(array_map(static fn (ItemSolicitado $i): string => $i->productoId, $solicitados)));
        $items = ValidadorCanasta::validar($solicitados, $this->fuente->unidadesBase($ids));

        $ordenados = OrdenCanasta::ordenar(
            CalculadoraCanasta::calcular($items, $this->fuente->ofertas($ids, $municipio)),
            $criterio,
        );

        return [
            'criterioOrden'        => ['campo' => $criterio, 'descripcion' => OrdenCanasta::descripcion($criterio)],
            'totalProductos'       => count($items),
            'items'                => array_map(static fn (ItemCanasta $i): array => [
                'productoId' => $i->productoId, 'cantidad' => $i->cantidad, 'unidadBase' => $i->unidadBase,
            ], $items),
            'generadoEn'           => (new DateTimeImmutable())->format(DATE_ATOM),
            'establecimientos'     => array_map(self::establecimiento(...), $ordenados, array_keys($ordenados)),
            'contenidoPromocional' => [],
        ];
    }

    /** @return array<string, mixed> */
    private static function establecimiento(ResultadoEstablecimiento $r, int $indice): array
    {
        return [
            'posicion'        => $indice + 1,
            'establecimiento' => ['id' => $r->establecimientoId, 'nombre' => $r->nombre],
            'total'           => $r->total,
            'disponibles'     => $r->disponibles(),
            'totalProductos'  => $r->totalProductos,
            'completa'        => $r->completa(),
            'faltantes'       => $r->faltantes,
            'lineas'          => array_map(static fn (LineaCanasta $l): array => [
                'productoId' => $l->productoId, 'precioPorUnidad' => $l->precioPorUnidad,
                'cantidad'   => $l->cantidad,   'subtotal'        => $l->subtotal,
            ], $r->lineas),
        ];
    }
}
