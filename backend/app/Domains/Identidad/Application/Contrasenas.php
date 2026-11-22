<?php

declare(strict_types=1);

namespace App\Domains\Identidad\Application;

/**
 * Hash de contraseñas con Argon2id (OWASP Password Storage). A diferencia de bcrypt, no trunca
 * a 72 bytes, así que la política de hasta 128 caracteres se respeta completa.
 */
final class Contrasenas
{
    /** Hash real (calculado una vez) para gastar el mismo tiempo cuando el correo no existe: evita enumerar cuentas por tiempo. */
    private static ?string $hashSenuelo = null;

    public function hash(string $contrasena): string
    {
        return password_hash($contrasena, PASSWORD_ARGON2ID);
    }

    public function verificar(string $contrasena, ?string $hash): bool
    {
        self::$hashSenuelo ??= password_hash('senuelo-sin-cuenta', PASSWORD_ARGON2ID);

        return password_verify($contrasena, $hash ?? self::$hashSenuelo) && $hash !== null;
    }
}
