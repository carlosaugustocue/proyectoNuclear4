<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Domain\Repositories;

use App\Domains\Comercial\Domain\ValueObjects\Promocion;
use App\Domains\Comercial\Domain\ValueObjects\PromocionRegistrada;
use DateTimeImmutable;

interface Promociones
{
    public function guardar(string $id, string $perfilId, string $productoId, Promocion $promocion): void;

    /** Marca la promoción como retirada si pertenece al perfil; false si no existe o es ajena. */
    public function retirar(string $id, string $perfilId, DateTimeImmutable $ahora): bool;

    /**
     * Candidatas a mostrarse para esos productos (la visibilidad final la decide RN-16).
     *
     * @param  list<string> $productoIds
     * @return list<PromocionRegistrada>
     */
    public function candidatasDeProductos(array $productoIds, DateTimeImmutable $ahora): array;
}
