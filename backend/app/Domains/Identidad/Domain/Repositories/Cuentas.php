<?php

declare(strict_types=1);

namespace App\Domains\Identidad\Domain\Repositories;

use App\Domains\Identidad\Domain\Entities\Cuenta;
use App\Domains\Identidad\Domain\ValueObjects\Correo;

interface Cuentas
{
    /** @throws \App\Domains\Identidad\Domain\Exceptions\CorreoYaRegistradoException */
    public function registrar(Cuenta $cuenta, string $contrasenaHash): void;

    /** Persiste rol y estado. */
    public function actualizar(Cuenta $cuenta): void;

    public function cambiarContrasena(string $cuentaId, string $contrasenaHash): void;

    public function porId(string $id): ?Cuenta;

    /** @return array{cuenta: Cuenta, contrasenaHash: string}|null */
    public function conCredencialPorCorreo(Correo $correo): ?array;

    public function contrasenaHash(string $cuentaId): ?string;

    /** RF-23: borra la cuenta y, en cascada, sus sesiones. */
    public function eliminar(string $cuentaId): void;
}
