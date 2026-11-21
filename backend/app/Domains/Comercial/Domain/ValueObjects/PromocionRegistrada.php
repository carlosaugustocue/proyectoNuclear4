<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Domain\ValueObjects;

use DateTimeImmutable;

/** Promoción guardada junto con lo necesario para decidir su visibilidad (RN-16). */
final class PromocionRegistrada
{
    public function __construct(
        public readonly string             $id,
        public readonly string             $establecimientoId,
        public readonly string             $productoId,
        public readonly Promocion          $promocion,
        public readonly EstadoPerfil       $estadoPerfil,
        public readonly ?DateTimeImmutable $retiradaEn,
    ) {}
}
