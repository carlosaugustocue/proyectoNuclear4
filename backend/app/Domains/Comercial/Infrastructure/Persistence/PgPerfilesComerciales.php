<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Infrastructure\Persistence;

use App\Domains\Comercial\Domain\Entities\PerfilComercial;
use App\Domains\Comercial\Domain\Repositories\PerfilesComerciales;
use App\Domains\Comercial\Domain\ValueObjects\EstadoPerfil;
use Illuminate\Support\Facades\DB;

final class PgPerfilesComerciales implements PerfilesComerciales
{
    private const TABLA = 'perfiles_comerciales';

    public function registrar(PerfilComercial $perfil, string $nombreContacto, ?string $telefonoContacto): void
    {
        DB::table(self::TABLA)->insert([
            'id'                 => $perfil->id,
            'establecimiento_id' => $perfil->establecimientoId,
            'nombre_contacto'    => $nombreContacto,
            'telefono_contacto'  => $telefonoContacto,
            'estado'             => $perfil->estado->value,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    public function actualizar(PerfilComercial $perfil, ?string $credencialHash = null): void
    {
        $cambios = ['estado' => $perfil->estado->value, 'updated_at' => now()];
        if ($credencialHash !== null) {
            $cambios += ['credencial_hash' => $credencialHash, 'verificado_en' => now()];
        }

        DB::table(self::TABLA)->where('id', $perfil->id)->update($cambios);
    }

    public function porId(string $id): ?PerfilComercial
    {
        return self::aPerfil(DB::table(self::TABLA)->where('id', $id)->first(['id', 'establecimiento_id', 'estado']));
    }

    public function porCredencialHash(string $hash): ?PerfilComercial
    {
        return self::aPerfil(DB::table(self::TABLA)->where('credencial_hash', $hash)->first(['id', 'establecimiento_id', 'estado']));
    }

    private static function aPerfil(?object $fila): ?PerfilComercial
    {
        if ($fila === null) {
            return null;
        }
        /** @var object{id: string, establecimiento_id: string, estado: string} $fila */
        return PerfilComercial::reconstituir($fila->id, $fila->establecimiento_id, EstadoPerfil::from($fila->estado));
    }
}
