<?php

declare(strict_types=1);

namespace App\Domains\Precios\Infrastructure\Catalogo;

use App\Domains\Catalogo\Application\ConsultarCatalogo;
use App\Domains\Precios\Domain\Repositories\ResolutorPresentaciones;

/** Adaptador: usa la interfaz pública del contexto Catálogo (AR-01). */
final class ResolutorPresentacionesCatalogo implements ResolutorPresentaciones
{
    public function __construct(private readonly ConsultarCatalogo $catalogo) {}

    public function productoDe(string $presentacionId): ?string
    {
        return $this->catalogo->presentacion($presentacionId)?->productoId;
    }
}
