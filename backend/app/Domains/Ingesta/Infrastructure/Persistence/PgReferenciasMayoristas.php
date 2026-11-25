<?php

declare(strict_types=1);

namespace App\Domains\Ingesta\Infrastructure\Persistence;

use App\Domains\Ingesta\Domain\Repositories\ReferenciasMayoristas;
use App\Domains\Ingesta\Domain\ValueObjects\ObservacionMayorista;
use Illuminate\Support\Facades\DB;

final class PgReferenciasMayoristas implements ReferenciasMayoristas
{
    public function guardar(string $productoId, ObservacionMayorista $observacion): void
    {
        DB::table('referencias_mayoristas')->upsert(
            [[
                'producto_id' => $productoId, 'mercado' => $observacion->mercado, 'mes' => $observacion->mes->format('Y-m-d'),
                'precio' => $observacion->precio, 'unidad_base' => $observacion->unidadBase,
                'codigo_cpc' => $observacion->codigoCpc, 'fuente' => 'SIPSA-DANE', 'actualizado_en' => now(),
            ]],
            ['producto_id', 'mercado', 'mes'],
            ['precio', 'unidad_base', 'codigo_cpc', 'actualizado_en'],
        );
    }

    public function serieMensual(string $productoId): array
    {
        $serie = DB::table('referencias_mayoristas')
            ->where('producto_id', $productoId)
            ->groupBy('mes')
            ->orderBy('mes')
            ->selectRaw("to_char(mes, 'YYYY-MM') AS mes, round(avg(precio))::bigint::text AS precio")
            ->get()
            ->map(static fn (object $f): array => ['mes' => (string) $f->mes, 'precio' => (string) $f->precio])
            ->all();

        $unidad = DB::table('referencias_mayoristas')->where('producto_id', $productoId)->orderByDesc('mes')->value('unidad_base');

        return [
            'unidadBase' => is_string($unidad) ? $unidad : null,
            'serie'      => array_values($serie),
            'mercados'   => array_values(DB::table('referencias_mayoristas')->where('producto_id', $productoId)
                ->distinct()->orderBy('mercado')->pluck('mercado')->map(static fn ($m): string => (string) $m)->all()),
        ];
    }
}
