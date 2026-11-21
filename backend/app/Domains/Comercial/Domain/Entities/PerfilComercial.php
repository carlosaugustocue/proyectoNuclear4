<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Domain\Entities;

use App\Domains\Comercial\Domain\ValueObjects\EstadoPerfil;
use DomainException;

/**
 * Perfil de un comercio vinculado a un establecimiento del directorio (RF-31, ADR-0022).
 * Inmutable: cada transición devuelve un perfil nuevo.
 */
final class PerfilComercial
{
    private function __construct(
        public readonly string       $id,
        public readonly string       $establecimientoId,
        public readonly EstadoPerfil $estado,
    ) {}

    public static function solicitar(string $id, string $establecimientoId): self
    {
        return new self($id, $establecimientoId, EstadoPerfil::PENDIENTE);
    }

    public static function reconstituir(string $id, string $establecimientoId, EstadoPerfil $estado): self
    {
        return new self($id, $establecimientoId, $estado);
    }

    public function verificar(): self
    {
        if ($this->estado !== EstadoPerfil::PENDIENTE) {
            throw new DomainException('Solo se puede verificar un perfil pendiente.');
        }

        return new self($this->id, $this->establecimientoId, EstadoPerfil::VERIFICADO);
    }

    public function revocar(): self
    {
        return new self($this->id, $this->establecimientoId, EstadoPerfil::REVOCADO);
    }
}
