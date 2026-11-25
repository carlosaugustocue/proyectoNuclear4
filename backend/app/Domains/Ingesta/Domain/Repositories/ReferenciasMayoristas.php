<?php

declare(strict_types=1);

namespace App\Domains\Ingesta\Domain\Repositories;

use App\Domains\Ingesta\Domain\ValueObjects\ObservacionMayorista;

interface ReferenciasMayoristas
{
    /** Inserta o actualiza el precio del producto en ese mercado y mes (idempotente). */
    public function guardar(string $productoId, ObservacionMayorista $observacion): void;

    /**
     * Serie mensual del producto: promedio de los mercados, redondeado a pesos enteros, del mes más antiguo al más reciente.
     *
     * @return array{unidadBase: ?string, serie: list<array{mes: string, precio: string}>, mercados: list<string>}
     */
    public function serieMensual(string $productoId): array;
}
