<?php

declare(strict_types=1);

namespace App\Domains\Canasta\Domain\Ports;

use App\Domains\Canasta\Domain\ValueObjects\OfertaProducto;

/** Puerto de la canasta hacia los precios vigentes (ADR-0017); lo implementa un adaptador sobre Comparación. */
interface FuentePreciosVigentes
{
    /**
     * @param  list<string>          $productoIds
     * @return array<string, string> productoId => unidad base (kg, L, unidad); omite los que no existen
     */
    public function unidadesBase(array $productoIds): array;

    /**
     * @param  list<string> $productoIds
     * @return list<OfertaProducto> solo precios vigentes, comparables con la unidad base del producto
     */
    public function ofertas(array $productoIds, ?string $municipio): array;
}
