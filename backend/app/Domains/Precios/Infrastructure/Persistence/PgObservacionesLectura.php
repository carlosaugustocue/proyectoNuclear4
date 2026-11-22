<?php

declare(strict_types=1);

namespace App\Domains\Precios\Infrastructure\Persistence;

use App\Domains\Precios\Domain\ValueObjects\DetalleObservacion;
use App\Domains\Precios\Domain\Repositories\ObservacionesLectura;
use App\Domains\Precios\Domain\ValueObjects\ObservacionPrecio;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class PgObservacionesLectura implements ObservacionesLectura
{
    public function ultimoPrecioPorEstablecimiento(string $presentacionId, string $excluirId, DateTimeImmutable $desde): array
    {
        $filas = DB::select(
            'SELECT DISTINCT ON (establecimiento_id) monto::text AS monto
               FROM registros_precio
              WHERE presentacion_id = ? AND id <> ? AND deleted_at IS NULL AND observado_en >= ?
              ORDER BY establecimiento_id, observado_en DESC',
            [$presentacionId, $excluirId, $desde->format(DATE_ATOM)],
        );

        return array_values(array_map(static fn (\stdClass $f): string => (string) $f->monto, $filas));
    }

    public function historialEnEstablecimiento(string $presentacionId, string $establecimientoId, string $excluirId, int $limite): array
    {
        return array_values(DB::table('registros_precio')
            ->where('presentacion_id', $presentacionId)
            ->where('establecimiento_id', $establecimientoId)
            ->where('id', '<>', $excluirId)
            ->orderByDesc('observado_en')
            ->limit($limite)
            ->pluck('monto')
            ->map(static fn (mixed $m): string => (string) $m)
            ->all());
    }

    public function recientesEnEstablecimiento(string $presentacionId, string $establecimientoId, DateTimeImmutable $desde): array
    {
        return array_values(DB::table('registros_precio')
            ->where('presentacion_id', $presentacionId)
            ->where('establecimiento_id', $establecimientoId)
            ->whereNull('deleted_at')
            ->where('observado_en', '>=', $desde->format(DATE_ATOM))
            ->orderByDesc('observado_en')
            ->get(['id', 'reportado_por', 'monto', 'observado_en'])
            ->map(static fn (\stdClass $f): ObservacionPrecio => new ObservacionPrecio(
                (string) $f->id,
                (string) $f->reportado_por,
                (string) $f->monto,
                new DateTimeImmutable((string) $f->observado_en),
            ))
            ->all());
    }

    public function primerAporteDe(string $reportadoPor): ?DateTimeImmutable
    {
        $primero = DB::table('registros_precio')->where('reportado_por', $reportadoPor)->min('registrado_en');

        return is_string($primero) ? new DateTimeImmutable($primero) : null;
    }

    private const COLUMNAS_DETALLE = ['id', 'producto_id', 'presentacion_id', 'establecimiento_id', 'monto', 'observado_en', 'fuente', 'reportado_por'];

    public function detalle(string $observacionId): ?DetalleObservacion
    {
        $f = DB::table('registros_precio')->where('id', $observacionId)->whereNull('deleted_at')->first(self::COLUMNAS_DETALLE);

        return $f === null ? null : self::aDetalle($f);
    }

    public function masRecienteEn(string $presentacionId, string $establecimientoId): ?string
    {
        $id = DB::table('registros_precio')->whereNull('deleted_at')
            ->where('presentacion_id', $presentacionId)->where('establecimiento_id', $establecimientoId)
            ->orderByDesc('observado_en')->orderByDesc('registrado_en')->value('id');

        return is_string($id) ? $id : null;
    }

    public function aportesDe(string $reportadoPor, int $pagina, int $tamano): array
    {
        $consulta = DB::table('registros_precio')->whereNull('deleted_at')->where('reportado_por', $reportadoPor);
        $total    = (clone $consulta)->count();
        $filas    = $consulta->orderByDesc('observado_en')->orderByDesc('registrado_en')
            ->offset(($pagina - 1) * $tamano)->limit($tamano)->get(self::COLUMNAS_DETALLE);

        return ['items' => array_values($filas->map(static fn (object $f): DetalleObservacion => self::aDetalle($f))->all()), 'total' => $total];
    }

    private static function aDetalle(object $f): DetalleObservacion
    {
        /** @var object{id: string, producto_id: string, presentacion_id: string, establecimiento_id: string, monto: string, observado_en: string, fuente: string, reportado_por: string} $f */
        return new DetalleObservacion(
            id:                (string) $f->id,
            productoId:        (string) $f->producto_id,
            presentacionId:    (string) $f->presentacion_id,
            establecimientoId: (string) $f->establecimiento_id,
            monto:             (string) $f->monto,
            observadoEn:       (new DateTimeImmutable((string) $f->observado_en))->format(DATE_ATOM),
            fuente:            (string) $f->fuente,
            reportadoPor:      (string) $f->reportado_por,
        );
    }
}
