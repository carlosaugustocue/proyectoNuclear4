<?php

declare(strict_types=1);

namespace App\Domains\Comparacion\Infrastructure;

use App\Domains\Comparacion\Domain\Repositories\ModeloLecturaComparacion;
use App\Domains\Confianza\Domain\Ports\PrecioPublicado;

/** Adaptador del puerto de Confianza: la fila del modelo de lectura es lo que la comparación muestra. */
final class PrecioPublicadoComparacion implements PrecioPublicado
{
    public function __construct(private readonly ModeloLecturaComparacion $modelo) {}

    public function observacionPublicada(string $presentacionId, string $establecimientoId): ?string
    {
        return $this->modelo->obtener($presentacionId, $establecimientoId)?->observacionId;
    }
}
