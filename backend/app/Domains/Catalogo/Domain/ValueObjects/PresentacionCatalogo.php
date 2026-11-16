<?php

declare(strict_types=1);

namespace App\Domains\Catalogo\Domain\ValueObjects;

/**
 * Vista pública de una presentación del catálogo: es lo que otros contextos
 * (Precios, Comparación) pueden conocer del Catálogo (interfaz pública, AR-01).
 */
final class PresentacionCatalogo
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $productoId,
        public readonly ?string $descripcion,
        public readonly string  $cantidad,
        public readonly string  $unidad,
        public readonly int     $unidadesPorEmpaque,
        public readonly string  $unidadBase,
        public readonly ?string $ean,
        public readonly string  $tipoVigencia,
    ) {}

    /** @return array{id: string, descripcion: ?string, cantidad: string, unidad: string, unidadesPorEmpaque: int, unidadBase: string, ean: ?string} */
    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'descripcion'        => $this->descripcion,
            'cantidad'           => $this->cantidad,
            'unidad'             => $this->unidad,
            'unidadesPorEmpaque' => $this->unidadesPorEmpaque,
            'unidadBase'         => $this->unidadBase,
            'ean'                => $this->ean,
        ];
    }
}
