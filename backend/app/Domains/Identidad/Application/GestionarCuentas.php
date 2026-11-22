<?php

declare(strict_types=1);

namespace App\Domains\Identidad\Application;

use App\Domains\Identidad\Domain\Entities\Cuenta;
use App\Domains\Identidad\Domain\Exceptions\ContrasenaRechazadaException;
use App\Domains\Identidad\Domain\Exceptions\CredencialesInvalidasException;
use App\Domains\Identidad\Domain\Repositories\Cuentas;
use App\Domains\Identidad\Domain\Repositories\Sesiones;
use App\Domains\Identidad\Domain\ValueObjects\Correo;
use App\Domains\Identidad\Domain\ValueObjects\PoliticaContrasena;
use App\Domains\Identidad\Domain\ValueObjects\Rol;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** RF-22 registro con consentimiento, RF-23 actualizar y suprimir, RF-24 roles. */
final class GestionarCuentas
{
    public function __construct(
        private readonly Cuentas           $cuentas,
        private readonly Sesiones          $sesiones,
        private readonly Contrasenas       $contrasenas,
        private readonly GestionarSesiones $gestionSesiones,
    ) {}

    /**
     * El llamador ya verificó la autorización expresa de tratamiento de datos (Ley 1581).
     *
     * @return array{cuenta: Cuenta, token: string, expiraEn: DateTimeImmutable}
     * @throws ContrasenaRechazadaException|\App\Domains\Identidad\Domain\Exceptions\CorreoYaRegistradoException
     */
    public function registrar(string $correo, string $contrasena, string $versionPolitica): array
    {
        $correoVo = Correo::de($correo);
        $motivos  = PoliticaContrasena::errores($contrasena, $correoVo);
        if ($motivos !== []) {
            throw new ContrasenaRechazadaException($motivos);
        }

        $cuenta = Cuenta::registrar((string) Str::uuid7(), $correoVo, Carbon::now()->toDateTimeImmutable(), $versionPolitica);
        $this->cuentas->registrar($cuenta, $this->contrasenas->hash($contrasena));

        return $this->gestionSesiones->abrirPara($cuenta);
    }

    public function obtener(string $cuentaId): ?Cuenta
    {
        return $this->cuentas->porId($cuentaId);
    }

    /** Cambia la contraseña con la actual y cierra todas las demás sesiones (por si la anterior se filtró). */
    public function cambiarContrasena(Cuenta $cuenta, string $tokenActual, string $actual, string $nueva): void
    {
        if (! $this->contrasenas->verificar($actual, $this->cuentas->contrasenaHash($cuenta->id))) {
            throw new ContrasenaRechazadaException(['La contraseña actual no es correcta.']);
        }
        $motivos = PoliticaContrasena::errores($nueva, $cuenta->correo);
        if ($motivos !== []) {
            throw new ContrasenaRechazadaException($motivos);
        }

        $this->cuentas->cambiarContrasena($cuenta->id, $this->contrasenas->hash($nueva));
        $this->sesiones->revocarTodas($cuenta->id, GestionarSesiones::hashDe($tokenActual));
    }

    /**
     * RF-23: suprime la cuenta (correo, hash, consentimiento y sesiones). Los aportes se conservan:
     * su `reportado_por` es un id opaco que, sin la fila de la cuenta, ya no identifica a nadie.
     */
    public function suprimir(string $cuentaId): void
    {
        $this->cuentas->eliminar($cuentaId);
    }

    /** RF-20: suspende una cuenta y cierra todas sus sesiones. */
    public function suspender(string $cuentaId): bool
    {
        $cuenta = $this->cuentas->porId($cuentaId);
        if ($cuenta === null) {
            return false;
        }
        $this->cuentas->actualizar($cuenta->suspender());
        $this->sesiones->revocarTodas($cuentaId);

        return true;
    }

    /** RF-24: el equipo promueve moderadores por consola (identidad confirmada fuera de banda). */
    public function promoverAModerador(string $correo): Cuenta
    {
        $registro = $this->cuentas->conCredencialPorCorreo(Correo::de($correo))
            ?? throw new CredencialesInvalidasException('No existe una cuenta con ese correo.');
        $cuenta = $registro['cuenta']->conRol(Rol::MODERADOR);
        $this->cuentas->actualizar($cuenta);

        return $cuenta;
    }
}
