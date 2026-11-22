<?php

declare(strict_types=1);

namespace App\Domains\Identidad\Infrastructure\Persistence;

use App\Domains\Identidad\Domain\Repositories\Sesiones;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class PgSesiones implements Sesiones
{
    private const TABLA = 'sesiones';

    public function abrir(string $id, string $cuentaId, string $tokenHash, DateTimeImmutable $creadaEn, DateTimeImmutable $expiraEn): void
    {
        DB::table(self::TABLA)->insert([
            'id' => $id, 'cuenta_id' => $cuentaId, 'token_hash' => $tokenHash, 'creado_en' => $creadaEn, 'expira_en' => $expiraEn,
        ]);
    }

    public function cuentaVigente(string $tokenHash, DateTimeImmutable $ahora): ?string
    {
        $id = DB::table(self::TABLA)->where('token_hash', $tokenHash)->where('expira_en', '>', $ahora)->value('cuenta_id');

        return is_string($id) ? $id : null;
    }

    public function revocar(string $tokenHash): void
    {
        DB::table(self::TABLA)->where('token_hash', $tokenHash)->delete();
    }

    public function revocarTodas(string $cuentaId, ?string $exceptoTokenHash = null): void
    {
        DB::table(self::TABLA)->where('cuenta_id', $cuentaId)
            ->when($exceptoTokenHash !== null, fn ($q) => $q->where('token_hash', '!=', $exceptoTokenHash))
            ->delete();
    }
}
