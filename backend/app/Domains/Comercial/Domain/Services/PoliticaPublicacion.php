<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Domain\Services;

use App\Domains\Comercial\Domain\Entities\PerfilComercial;
use App\Domains\Comercial\Domain\Exceptions\PublicacionNoAutorizadaException;
use App\Domains\Comercial\Domain\ValueObjects\EstadoPerfil;

/** RN-15: solo un perfil VERIFICADO publica, y solo sobre su propio establecimiento. */
final class PoliticaPublicacion
{
    public static function autorizar(PerfilComercial $perfil, string $establecimientoId): void
    {
        match ($perfil->estado) {
            EstadoPerfil::PENDIENTE  => throw new PublicacionNoAutorizadaException('El perfil comercial aún no está verificado.'),
            EstadoPerfil::REVOCADO   => throw new PublicacionNoAutorizadaException('El perfil comercial fue revocado.'),
            EstadoPerfil::VERIFICADO => null,
        };

        if ($perfil->establecimientoId !== $establecimientoId) {
            throw new PublicacionNoAutorizadaException('El perfil solo puede publicar sobre su propio establecimiento.');
        }
    }
}
