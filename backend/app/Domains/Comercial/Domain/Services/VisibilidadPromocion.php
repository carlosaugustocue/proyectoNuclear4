<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Domain\Services;

use App\Domains\Comercial\Domain\ValueObjects\EstadoPerfil;
use App\Domains\Comercial\Domain\ValueObjects\Promocion;
use DateTimeImmutable;

/** RN-16: visible si el perfil está verificado, no fue retirada y ahora ∈ [inicio, fin]. */
final class VisibilidadPromocion
{
    public static function esVisible(
        Promocion          $promocion,
        EstadoPerfil       $estadoPerfil,
        ?DateTimeImmutable $retiradaEn,
        DateTimeImmutable  $ahora,
    ): bool {
        if ($estadoPerfil !== EstadoPerfil::VERIFICADO) {
            return false;
        }
        if ($retiradaEn !== null && $ahora >= $retiradaEn) {
            return false;
        }

        return $ahora >= $promocion->inicio && $ahora <= $promocion->fin;
    }
}
