<?php

declare(strict_types=1);

namespace App\Domains\Identidad\Domain\ValueObjects;

/**
 * Política de contraseñas (NIST SP 800-63B: longitud antes que reglas de composición).
 * Devuelve TODOS los motivos de rechazo para mostrarlos juntos en el formulario.
 */
final class PoliticaContrasena
{
    public const MINIMO = 10;
    public const MAXIMO = 128;

    /** @return list<string> */
    public static function errores(string $contrasena, ?Correo $correo = null): array
    {
        $errores = [];
        $largo   = mb_strlen($contrasena);
        if ($largo < self::MINIMO) {
            $errores[] = 'Debe tener al menos ' . self::MINIMO . ' caracteres.';
        }
        if ($largo > self::MAXIMO) {
            $errores[] = 'Debe tener como máximo ' . self::MAXIMO . ' caracteres.';
        }
        if ($largo > 0 && ctype_digit($contrasena)) {
            $errores[] = 'No puede ser solo números.';
        }
        if ($correo !== null && str_contains(mb_strtolower($contrasena), $correo->valor)) {
            $errores[] = 'No puede contener tu correo.';
        }

        return $errores;
    }
}
