<?php

declare(strict_types=1);

namespace App\Domains\Precios\Infrastructure\Persistence;

use App\Domains\Precios\Domain\Entities\RegistroPrecio;
use App\Domains\Precios\Domain\Repositories\RegistroPrecioRepository;
use Illuminate\Support\Facades\DB;

final class EloquentRegistroPrecioRepository implements RegistroPrecioRepository
{
    public function save(RegistroPrecio $registro): void
    {
        DB::table('registros_precio')->insert([
            'id'                 => $registro->id(),
            'producto_id'        => $registro->productoId(),
            'establecimiento_id' => $registro->establecimientoId(),
            'monto'              => $registro->precio()->monto(),
            'moneda'             => $registro->precio()->moneda(),
            'ubicacion'          => DB::raw(
                "ST_GeogFromText('{$registro->coordenadas()->toWkt()}')"
            ),
            'reportado_por'      => $registro->reportadoPor(),
            'registrado_en'      => $registro->registradoEn()->format('Y-m-d H:i:sP'),
        ]);
    }
}
