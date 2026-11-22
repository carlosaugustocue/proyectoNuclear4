<?php

declare(strict_types=1);

namespace App\Domains\Identidad\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Correo de una cuenta (RF-22). Se valida aquí y NO con la regla `email` de Laravel 11, que tiene
 * una inyección CRLF sin corregir en esa rama (GHSA-5vg9-5847-vvmq; ADR-0012): cualquier carácter
 * de control se rechaza antes de llegar a la persistencia.
 */
final class Correo
{
    public const LONGITUD_MAXIMA = 120;

    /** local@dominio.tld, sin espacios; el dominio necesita al menos un punto. */
    private const PATRON = '/^[a-z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+$/D';

    private function __construct(public readonly string $valor) {}

    public static function de(string $valor): self
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $valor) === 1) {
            throw new InvalidArgumentException('El correo contiene caracteres no permitidos.');
        }
        $limpio = mb_strtolower(trim($valor));
        if ($limpio === '' || mb_strlen($limpio) > self::LONGITUD_MAXIMA || preg_match(self::PATRON, $limpio) !== 1) {
            throw new InvalidArgumentException('El correo no es válido.');
        }

        return new self($limpio);
    }
}
