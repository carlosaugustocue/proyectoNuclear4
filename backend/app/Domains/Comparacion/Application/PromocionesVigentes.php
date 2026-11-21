<?php

declare(strict_types=1);

namespace App\Domains\Comparacion\Application;

/**
 * Puerto que Comparación declara para llenar `contenidoPromocional` (ADR-0009, ADR-0023).
 * Lo implementa un contexto periférico (Comercial); Comparación nunca depende de él (AR-02).
 * Las promociones jamás entran a OrdenComparacion ni a los totales.
 */
interface PromocionesVigentes
{
    /**
     * @param  list<string> $productoIds
     * @return list<array{tipo: string, descripcion: string, establecimiento: array{id: string, nombre: string}, productoId: string, vigenteHasta: string, esPromocion: true}>
     */
    public function deProductos(array $productoIds): array;
}
