<?php

declare(strict_types=1);

namespace App\Domains\Canasta\Infrastructure;

use App\Domains\Canasta\Domain\Ports\FuentePreciosVigentes;
use App\Domains\Canasta\Domain\ValueObjects\OfertaProducto;
use App\Domains\Comparacion\Application\ConsultarPreciosVigentes;

/** Adaptador: único punto de contacto de la canasta con Comparación (AR-01, ADR-0017). */
final class FuentePreciosVigentesComparacion implements FuentePreciosVigentes
{
    public function __construct(private readonly ConsultarPreciosVigentes $comparacion) {}

    public function unidadesBase(array $productoIds): array
    {
        return $this->comparacion->unidadesBase($productoIds);
    }

    public function ofertas(array $productoIds, ?string $municipio): array
    {
        $unidades = $this->comparacion->unidadesBase($productoIds);
        $ofertas = [];
        foreach ($this->comparacion->vigentes($productoIds, $municipio) as $v) {
            // RN-03: solo presentaciones con la misma unidad base que el producto
            if (($unidades[$v['productoId']] ?? null) !== $v['unidadBase']) {
                continue;
            }
            $ofertas[] = new OfertaProducto($v['productoId'], $v['establecimientoId'], $v['establecimientoNombre'], $v['precioPorUnidad']);
        }

        return $ofertas;
    }
}
