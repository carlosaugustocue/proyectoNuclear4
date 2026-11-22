<?php

declare(strict_types=1);

namespace App\Domains\Identidad\Application;

use App\Domains\Identidad\Domain\Entities\Cuenta;
use App\Domains\Identidad\Domain\Exceptions\CredencialesInvalidasException;
use App\Domains\Identidad\Domain\Repositories\Cuentas;
use App\Domains\Identidad\Domain\Repositories\Sesiones;
use App\Domains\Identidad\Domain\ValueObjects\Correo;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * ADR-0025: sesión con token Bearer opaco de 256 bits. En la base solo queda su SHA-256; vence a
 * las 8 horas y se puede revocar. La PWA lo guarda solo en memoria (nunca en localStorage).
 */
final class GestionarSesiones
{
    public const DURACION_HORAS = 8;
    private const BYTES_TOKEN   = 32;

    public function __construct(
        private readonly Cuentas     $cuentas,
        private readonly Sesiones    $sesiones,
        private readonly Contrasenas $contrasenas,
    ) {}

    /**
     * @return array{cuenta: Cuenta, token: string, expiraEn: DateTimeImmutable}
     * @throws CredencialesInvalidasException mismo error para correo inexistente, contraseña mala o cuenta suspendida
     */
    public function iniciar(string $correo, string $contrasena): array
    {
        try {
            $registro = $this->cuentas->conCredencialPorCorreo(Correo::de($correo));
        } catch (InvalidArgumentException) {
            $registro = null;
        }
        $valida = $this->contrasenas->verificar($contrasena, $registro['contrasenaHash'] ?? null);

        if ($registro === null || ! $valida || ! $registro['cuenta']->puedeIniciarSesion()) {
            throw new CredencialesInvalidasException('Correo o contraseña incorrectos.');
        }

        return $this->abrirPara($registro['cuenta']);
    }

    /** @return array{cuenta: Cuenta, token: string, expiraEn: DateTimeImmutable} */
    public function abrirPara(Cuenta $cuenta): array
    {
        $token    = bin2hex(random_bytes(self::BYTES_TOKEN));
        $ahora    = self::ahora();
        $expiraEn = $ahora->modify('+' . self::DURACION_HORAS . ' hours');
        $this->sesiones->abrir((string) Str::uuid7(), $cuenta->id, self::hashDe($token), $ahora, $expiraEn);

        return ['cuenta' => $cuenta, 'token' => $token, 'expiraEn' => $expiraEn];
    }

    /** Cuenta ACTIVA dueña de un token vigente, o null. */
    public function autenticar(string $token): ?Cuenta
    {
        $cuentaId = $this->sesiones->cuentaVigente(self::hashDe($token), self::ahora());
        $cuenta   = $cuentaId === null ? null : $this->cuentas->porId($cuentaId);

        return $cuenta !== null && $cuenta->puedeIniciarSesion() ? $cuenta : null;
    }

    public function cerrar(string $token): void
    {
        $this->sesiones->revocar(self::hashDe($token));
    }

    public static function hashDe(string $token): string
    {
        return hash('sha256', $token);
    }

    private static function ahora(): DateTimeImmutable
    {
        return Carbon::now()->toDateTimeImmutable();
    }
}
