<?php

declare(strict_types=1);

namespace App\Domains\Identidad\Domain\Repositories;

use DateTimeImmutable;

interface Sesiones
{
    public function abrir(string $id, string $cuentaId, string $tokenHash, DateTimeImmutable $creadaEn, DateTimeImmutable $expiraEn): void;

    /** Id de la cuenta dueña de una sesión no vencida, o null. */
    public function cuentaVigente(string $tokenHash, DateTimeImmutable $ahora): ?string;

    public function revocar(string $tokenHash): void;

    /** Revoca todas las sesiones de la cuenta, menos (opcionalmente) la indicada. */
    public function revocarTodas(string $cuentaId, ?string $exceptoTokenHash = null): void;
}
