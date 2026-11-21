<?php

declare(strict_types=1);

namespace App\Domains\Comparacion\Application;

/** Implementación por defecto: sin un contexto que publique promociones, el bloque viaja vacío. */
final class SinPromociones implements PromocionesVigentes
{
    public function deProductos(array $productoIds): array
    {
        return [];
    }
}
