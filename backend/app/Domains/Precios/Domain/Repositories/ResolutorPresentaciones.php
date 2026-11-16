<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Repositories;

/** Puerto hacia el Catálogo: Precios solo necesita saber a qué producto pertenece una presentación. */
interface ResolutorPresentaciones
{
    public function productoDe(string $presentacionId): ?string;
}
