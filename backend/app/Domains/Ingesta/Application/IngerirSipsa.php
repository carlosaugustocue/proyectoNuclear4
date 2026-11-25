<?php

declare(strict_types=1);

namespace App\Domains\Ingesta\Application;

use App\Domains\Catalogo\Domain\Repositories\CatalogoEscritura;
use App\Domains\Ingesta\Domain\Exceptions\FilaSipsaInvalidaException;
use App\Domains\Ingesta\Domain\Repositories\ReferenciasMayoristas;
use App\Domains\Ingesta\Domain\Services\TraductorSipsa;

/**
 * Caso de uso de la carga de SIPSA (RF-25, RF-26, ADR-0010): traduce cada fila (capa anticorrupción),
 * asegura el producto genérico en el catálogo y guarda la referencia mayorista. Una fila inválida se
 * omite y se reporta; no detiene el resto. No crea observaciones de precio: la comparación no cambia.
 */
final class IngerirSipsa
{
    public function __construct(
        private readonly CatalogoEscritura $catalogo,
        private readonly ReferenciasMayoristas $referencias,
    ) {}

    /**
     * @param  iterable<int, array<string, string|null>> $filas número de línea => fila
     * @return array{productosNuevos: int, productosExistentes: int, referencias: int, rechazadas: list<array{linea: int, motivo: string}>}
     */
    public function ejecutar(iterable $filas): array
    {
        /** @var array<string, string> $productos nombre → id, para no consultar el catálogo en cada fila */
        $productos = [];
        $resultado = ['productosNuevos' => 0, 'productosExistentes' => 0, 'referencias' => 0, 'rechazadas' => []];

        foreach ($filas as $linea => $fila) {
            try {
                $observacion = TraductorSipsa::traducir($fila);
            } catch (FilaSipsaInvalidaException $e) {
                $resultado['rechazadas'][] = ['linea' => $linea, 'motivo' => $e->getMessage()];
                continue;
            }

            if (! isset($productos[$observacion->producto])) {
                $producto = $this->catalogo->asegurarProductoGenerico(
                    $observacion->producto, $observacion->categoria, $observacion->tipoVigencia, $observacion->unidadBase,
                );
                $productos[$observacion->producto] = $producto['id'];
                $producto['creado'] ? $resultado['productosNuevos']++ : $resultado['productosExistentes']++;
            }

            $this->referencias->guardar($productos[$observacion->producto], $observacion);
            $resultado['referencias']++;
        }

        return $resultado;
    }
}
