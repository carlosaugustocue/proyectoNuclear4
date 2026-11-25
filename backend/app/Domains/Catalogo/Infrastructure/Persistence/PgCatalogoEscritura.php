<?php

declare(strict_types=1);

namespace App\Domains\Catalogo\Infrastructure\Persistence;

use App\Domains\Catalogo\Domain\Repositories\CatalogoEscritura;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class PgCatalogoEscritura implements CatalogoEscritura
{
    /** Descripción de la presentación de una unidad base. */
    private const PRESENTACION = ['KG' => 'Por kilo', 'L' => 'Por litro', 'UNIDAD' => 'Unidad'];

    public function asegurarProductoGenerico(string $nombre, string $categoria, string $tipoVigencia, string $unidadBase): array
    {
        $existente = DB::table('productos')
            ->whereNull('marca')
            ->whereRaw("lower(unaccent(btrim(regexp_replace(nombre, '\\s+', ' ', 'g')))) = lower(unaccent(?))", [$nombre])
            ->value('id');
        if (is_string($existente)) {
            return ['id' => $existente, 'creado' => false];
        }

        $productoId = Uuid::uuid7()->toString();
        DB::transaction(static function () use ($productoId, $nombre, $categoria, $tipoVigencia, $unidadBase): void {
            DB::table('productos')->insert([
                'id' => $productoId, 'nombre' => $nombre, 'marca' => null, 'categoria' => $categoria,
                'tipo_vigencia' => $tipoVigencia, 'alias' => '[]', 'estado_catalogo' => 'APROBADO',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('presentaciones')->insert([
                'id' => Uuid::uuid7()->toString(), 'producto_id' => $productoId,
                'descripcion' => self::PRESENTACION[$unidadBase], 'cantidad' => '1', 'unidad' => $unidadBase,
                'unidades_por_empaque' => 1, 'unidad_base' => $unidadBase,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return ['id' => $productoId, 'creado' => true];
    }
}
