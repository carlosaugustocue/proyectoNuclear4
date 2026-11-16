<?php

declare(strict_types=1);

namespace App\Domains\Catalogo\Application;

use App\Domains\Catalogo\Domain\Repositories\CatalogoLectura;
use App\Domains\Catalogo\Domain\ValueObjects\PresentacionCatalogo;

/** Interfaz pública del contexto Catálogo (RF-01, RF-02). */
final class ConsultarCatalogo
{
    public function __construct(private readonly CatalogoLectura $lectura) {}

    /** @return array{items: list<array{id: string, nombre: string, marca: ?string, categoria: string}>, total: int} */
    public function buscar(?string $texto, ?string $ean, ?string $categoria, int $pagina, int $tamano): array
    {
        return $this->lectura->buscar($texto, $ean, $categoria, $pagina, $tamano);
    }

    /** @return array<string, mixed>|null Producto según el esquema `Producto` del contrato */
    public function detalle(string $productoId): ?array
    {
        $resumen = $this->lectura->resumen($productoId);
        if ($resumen === null) {
            return null;
        }

        return array_merge($resumen, [
            'alias'          => $this->lectura->aliasDe($productoId),
            'estadoCatalogo' => $this->lectura->estadoCatalogo($productoId),
            'presentaciones' => array_map(
                static fn (PresentacionCatalogo $p): array => $p->toArray(),
                $this->lectura->presentacionesDe($productoId),
            ),
        ]);
    }

    /** @return array{id: string, nombre: string, marca: ?string, categoria: string}|null */
    public function resumen(string $productoId): ?array
    {
        return $this->lectura->resumen($productoId);
    }

    public function presentacion(string $presentacionId): ?PresentacionCatalogo
    {
        return $this->lectura->presentacion($presentacionId);
    }
}
