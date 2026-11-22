<?php

declare(strict_types=1);

namespace App\Domains\Identidad\Infrastructure\Persistence;

use App\Domains\Identidad\Domain\Entities\Cuenta;
use App\Domains\Identidad\Domain\Exceptions\CorreoYaRegistradoException;
use App\Domains\Identidad\Domain\Repositories\Cuentas;
use App\Domains\Identidad\Domain\ValueObjects\Correo;
use App\Domains\Identidad\Domain\ValueObjects\EstadoCuenta;
use App\Domains\Identidad\Domain\ValueObjects\Rol;
use DateTimeImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

final class PgCuentas implements Cuentas
{
    private const TABLA    = 'cuentas';
    private const COLUMNAS = ['id', 'correo', 'rol', 'estado', 'consentimiento_en', 'version_politica'];

    public function registrar(Cuenta $cuenta, string $contrasenaHash): void
    {
        try {
            DB::table(self::TABLA)->insert([
                'id'                => $cuenta->id,
                'correo'            => $cuenta->correo->valor,
                'contrasena_hash'   => $contrasenaHash,
                'rol'               => $cuenta->rol->value,
                'estado'            => $cuenta->estado->value,
                'consentimiento_en' => $cuenta->consentimientoEn,
                'version_politica'  => $cuenta->versionPolitica,
                'creado_en'         => now(),
                'actualizado_en'    => now(),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            throw new CorreoYaRegistradoException('Ya existe una cuenta con ese correo.', 0, $e);
        }
    }

    public function actualizar(Cuenta $cuenta): void
    {
        DB::table(self::TABLA)->where('id', $cuenta->id)
            ->update(['rol' => $cuenta->rol->value, 'estado' => $cuenta->estado->value, 'actualizado_en' => now()]);
    }

    public function cambiarContrasena(string $cuentaId, string $contrasenaHash): void
    {
        DB::table(self::TABLA)->where('id', $cuentaId)->update(['contrasena_hash' => $contrasenaHash, 'actualizado_en' => now()]);
    }

    public function porId(string $id): ?Cuenta
    {
        $fila = DB::table(self::TABLA)->where('id', $id)->first(self::COLUMNAS);

        return $fila === null ? null : self::aCuenta($fila);
    }

    public function conCredencialPorCorreo(Correo $correo): ?array
    {
        /** @var (object{contrasena_hash: string}&\stdClass)|null $fila */
        $fila = DB::table(self::TABLA)->where('correo', $correo->valor)->first([...self::COLUMNAS, 'contrasena_hash']);

        return $fila === null ? null : ['cuenta' => self::aCuenta($fila), 'contrasenaHash' => $fila->contrasena_hash];
    }

    public function contrasenaHash(string $cuentaId): ?string
    {
        $hash = DB::table(self::TABLA)->where('id', $cuentaId)->value('contrasena_hash');

        return is_string($hash) ? $hash : null;
    }

    public function eliminar(string $cuentaId): void
    {
        DB::table(self::TABLA)->where('id', $cuentaId)->delete();
    }

    private static function aCuenta(object $f): Cuenta
    {
        /** @var object{id: string, correo: string, rol: string, estado: string, consentimiento_en: string, version_politica: string} $f */
        return Cuenta::reconstituir(
            $f->id, Correo::de($f->correo), Rol::from($f->rol), EstadoCuenta::from($f->estado),
            new DateTimeImmutable($f->consentimiento_en), $f->version_politica,
        );
    }
}
