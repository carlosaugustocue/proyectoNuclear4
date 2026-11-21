<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Domain\Repositories;

use App\Domains\Comercial\Domain\ValueObjects\EstadoPerfil;

use App\Domains\Comercial\Domain\Entities\PerfilComercial;

interface PerfilesComerciales
{
    /** Guarda un perfil nuevo con sus datos de contacto privados (nunca salen en respuestas públicas). */
    public function registrar(PerfilComercial $perfil, string $nombreContacto, ?string $telefonoContacto): void;

    /** Persiste el estado; con $credencialHash, también el hash de la credencial emitida (ADR-0022). */
    public function actualizar(PerfilComercial $perfil, ?string $credencialHash = null): void;

    public function porId(string $id): ?PerfilComercial;

    public function porCredencialHash(string $hash): ?PerfilComercial;

    /**
     * Perfiles por estado, los más antiguos primero. Sin datos de contacto (EC-07).
     *
     * @return array{items: list<array{perfil: PerfilComercial, solicitadoEn: string}>, total: int}
     */
    public function listar(EstadoPerfil $estado, int $pagina, int $tamano): array;
}
