<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Infrastructure\Persistence;

use App\Domains\Confianza\Domain\Repositories\EvaluacionesRepositorio;
use Illuminate\Support\Facades\DB;

final class PgEvaluacionesRepositorio implements EvaluacionesRepositorio
{
    public function guardar(string $observacionId, string $reportadoPor, string $estado, int $confirmaciones, string $permiso): void
    {
        DB::table('evaluaciones_confianza')->upsert([[
            'observacion_id' => $observacionId,
            'reportado_por'  => $reportadoPor,
            'estado'         => $estado,
            'confirmaciones' => $confirmaciones,
            'permiso'        => $permiso,
            'evaluado_en'    => now(),
        ]], ['observacion_id'], ['estado', 'confirmaciones', 'permiso', 'evaluado_en']);
    }

    public function marcarConfirmadas(array $observacionIds): void
    {
        if ($observacionIds !== []) {
            DB::table('evaluaciones_confianza')->whereIn('observacion_id', $observacionIds)->update(['confirmada' => true]);
        }
    }

    public function aportesConfirmadosDe(string $reportadoPor): int
    {
        return DB::table('evaluaciones_confianza')->where('reportado_por', $reportadoPor)->where('confirmada', true)->count();
    }
}
