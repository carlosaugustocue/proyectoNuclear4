<?php

declare(strict_types=1);

namespace App\Domains\Catalogo\Domain\Repositories;

use App\Domains\Catalogo\Domain\ValueObjects\PresentacionCatalogo;

/**
 * Puerto de lectura del catálogo.
 *
 * @phpstan-type ProductoResumen array{id: string, nombre: string, marca: ?string, categoria: string}
 */
interface CatalogoLectura
{
    /** @return array{items: list<array{id: string, nombre: string, marca: ?string, categoria: string}>, total: int} */
    public function buscar(?string $texto, ?string $ean, ?string $categoria, int $pagina, int $tamano): array;

    /** @return array{id: string, nombre: string, marca: ?string, categoria: string}|null */
    public function resumen(string $productoId): ?array;

    /** @return list<PresentacionCatalogo> */
    public function presentacionesDe(string $productoId): array;

    /** @return list<string> */
    public function aliasDe(string $productoId): array;

    public function estadoCatalogo(string $productoId): ?string;

    public function presentacion(string $presentacionId): ?PresentacionCatalogo;

    /**
     * Unidad base de la primera presentación (por cantidad) de cada producto, en UNA consulta.
     *
     * @param  list<string>          $productoIds
     * @return array<string, string> productoId => unidad base (KG, L, UNIDAD); omite los que no tienen presentaciones
     */
    public function unidadesBase(array $productoIds): array;
}
