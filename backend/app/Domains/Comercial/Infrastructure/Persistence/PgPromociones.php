<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Infrastructure\Persistence;

use App\Domains\Comercial\Domain\Repositories\Promociones;
use App\Domains\Comercial\Domain\ValueObjects\EstadoPerfil;
use App\Domains\Comercial\Domain\ValueObjects\Promocion;
use App\Domains\Comercial\Domain\ValueObjects\PromocionRegistrada;
use App\Domains\Comercial\Domain\ValueObjects\TipoPromocion;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class PgPromociones implements Promociones
{
    public function guardar(string $id, string $perfilId, string $productoId, Promocion $promocion): void
    {
        DB::table('promociones')->insert([
            'id'                  => $id,
            'perfil_comercial_id' => $perfilId,
            'producto_id'         => $productoId,
            'tipo'                => $promocion->tipo->value,
            'valor'               => $promocion->valor,
            'pague'               => $promocion->pague,
            'lleve'               => $promocion->lleve,
            'inicio'              => $promocion->inicio->format(DATE_ATOM),
            'fin'                 => $promocion->fin->format(DATE_ATOM),
            'created_at'          => now(),
        ]);
    }

    public function retirar(string $id, string $perfilId, DateTimeImmutable $ahora): bool
    {
        return DB::table('promociones')
            ->where('id', $id)
            ->where('perfil_comercial_id', $perfilId)
            ->whereNull('retirada_en')
            ->update(['retirada_en' => $ahora->format(DATE_ATOM)]) === 1;
    }

    public function candidatasDeProductos(array $productoIds, DateTimeImmutable $ahora): array
    {
        if ($productoIds === []) {
            return [];
        }

        $filas = DB::table('promociones as p')
            ->join('perfiles_comerciales as pc', 'pc.id', '=', 'p.perfil_comercial_id')
            ->whereIn('p.producto_id', $productoIds)
            ->where('p.fin', '>=', $ahora->format(DATE_ATOM))
            ->orderBy('p.fin')
            ->get(['p.id', 'p.producto_id', 'p.tipo', 'p.valor', 'p.pague', 'p.lleve', 'p.inicio', 'p.fin',
                'p.retirada_en', 'pc.establecimiento_id', 'pc.estado']);

        return array_values($filas->map(static function (object $f): PromocionRegistrada {
            /** @var object{id: string, producto_id: string, tipo: string, valor: ?string, pague: ?int, lleve: ?int, inicio: string, fin: string, retirada_en: ?string, establecimiento_id: string, estado: string} $f */
            return new PromocionRegistrada(
                $f->id,
                $f->establecimiento_id,
                $f->producto_id,
                new Promocion(
                    TipoPromocion::from($f->tipo),
                    $f->valor,
                    $f->pague === null ? null : (int) $f->pague,
                    $f->lleve === null ? null : (int) $f->lleve,
                    new DateTimeImmutable($f->inicio),
                    new DateTimeImmutable($f->fin),
                ),
                EstadoPerfil::from($f->estado),
                $f->retirada_en === null ? null : new DateTimeImmutable($f->retirada_en),
            );
        })->all());
    }
}
