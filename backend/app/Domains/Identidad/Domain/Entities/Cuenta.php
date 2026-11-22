<?php

declare(strict_types=1);

namespace App\Domains\Identidad\Domain\Entities;

use App\Domains\Identidad\Domain\ValueObjects\Correo;
use App\Domains\Identidad\Domain\ValueObjects\EstadoCuenta;
use App\Domains\Identidad\Domain\ValueObjects\Rol;
use DateTimeImmutable;
use DomainException;

/**
 * Cuenta de usuario (RF-22). El consentimiento de tratamiento de datos (Ley 1581/2012) es
 * obligatorio para existir: se guarda la fecha y la versión de la política aceptada.
 */
final class Cuenta
{
    private function __construct(
        public readonly string            $id,
        public readonly Correo            $correo,
        public readonly Rol               $rol,
        public readonly EstadoCuenta      $estado,
        public readonly DateTimeImmutable $consentimientoEn,
        public readonly string            $versionPolitica,
    ) {}

    public static function registrar(string $id, Correo $correo, DateTimeImmutable $consentimientoEn, string $versionPolitica): self
    {
        return new self($id, $correo, Rol::CIUDADANO, EstadoCuenta::ACTIVA, $consentimientoEn, $versionPolitica);
    }

    public static function reconstituir(
        string $id, Correo $correo, Rol $rol, EstadoCuenta $estado, DateTimeImmutable $consentimientoEn, string $versionPolitica,
    ): self {
        return new self($id, $correo, $rol, $estado, $consentimientoEn, $versionPolitica);
    }

    public function puedeIniciarSesion(): bool
    {
        return $this->estado === EstadoCuenta::ACTIVA;
    }

    /** RF-20: un moderador suspende la cuenta de un reportante malintencionado. */
    public function suspender(): self
    {
        if ($this->rol === Rol::MODERADOR) {
            throw new DomainException('Una cuenta de moderador no se suspende desde la moderación.');
        }

        return new self($this->id, $this->correo, $this->rol, EstadoCuenta::SUSPENDIDA, $this->consentimientoEn, $this->versionPolitica);
    }

    public function conRol(Rol $rol): self
    {
        return new self($this->id, $this->correo, $rol, $this->estado, $this->consentimientoEn, $this->versionPolitica);
    }

    /** Nombre público, sin datos personales (EC-07): «Usuario» + los últimos 4 caracteres del id. */
    public function seudonimo(): string
    {
        return 'Usuario ' . strtoupper(substr(str_replace('-', '', $this->id), -4));
    }
}
