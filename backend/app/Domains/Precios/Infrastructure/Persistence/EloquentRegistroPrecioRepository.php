<?php

declare(strict_types=1);

namespace App\Domains\Precios\Infrastructure\Persistence;

use App\Domains\Precios\Domain\Entities\RegistroPrecio;
use App\Domains\Precios\Domain\Repositories\RegistroPrecioRepository;
use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class EloquentRegistroPrecioRepository implements RegistroPrecioRepository
{
    public function save(RegistroPrecio $registro): void
    {
        DB::table('registros_precio')->insert([
            'id'                 => $registro->id(),
            'producto_id'        => $registro->productoId(),
            'presentacion_id'    => $registro->presentacionId(),
            'establecimiento_id' => $registro->establecimientoId(),
            'monto'              => $registro->precio()->monto(),
            'moneda'             => $registro->precio()->moneda(),
            'reportado_por'      => $registro->reportadoPor(),
            'observado_en'       => $registro->observadoEn()->format(DATE_ATOM),
            'registrado_en'      => $registro->registradoEn()->format(DATE_ATOM),
            'clave_idempotencia' => $registro->claveIdempotencia(),
            'fuente'             => $registro->fuente()->value,
        ]);
    }

    public function idPorClaveIdempotencia(string $clave): ?string
    {
        $id = DB::table('registros_precio')->where('clave_idempotencia', $clave)->value('id');

        return is_string($id) ? $id : null;
    }

    public function contarAportesDesde(string $reportadoPor, DateTimeImmutable $desde): int
    {
        return DB::table('registros_precio')
            ->where('reportado_por', $reportadoPor)
            ->where('registrado_en', '>=', $desde->format(DATE_ATOM))
            ->count();
    }

    public function existeAporteDelDia(
        string $reportadoPor,
        string $presentacionId,
        string $establecimientoId,
        DateTimeImmutable $inicioDia,
        DateTimeImmutable $finDia,
    ): bool {
        return $this->delDia($reportadoPor, $presentacionId, $establecimientoId, $inicioDia, $finDia)->exists();
    }

    public function retirarAportesDelDia(
        string $reportadoPor,
        string $presentacionId,
        string $establecimientoId,
        DateTimeImmutable $inicioDia,
        DateTimeImmutable $finDia,
    ): int {
        return $this->delDia($reportadoPor, $presentacionId, $establecimientoId, $inicioDia, $finDia)
            ->update(['deleted_at' => now()]);
    }

    private function delDia(
        string $reportadoPor,
        string $presentacionId,
        string $establecimientoId,
        DateTimeImmutable $inicioDia,
        DateTimeImmutable $finDia,
    ): Builder {
        return DB::table('registros_precio')
            ->whereNull('deleted_at')
            ->where('reportado_por', $reportadoPor)
            ->where('presentacion_id', $presentacionId)
            ->where('establecimiento_id', $establecimientoId)
            ->whereBetween('observado_en', [$inicioDia->format(DATE_ATOM), $finDia->format(DATE_ATOM)]);
    }
}
