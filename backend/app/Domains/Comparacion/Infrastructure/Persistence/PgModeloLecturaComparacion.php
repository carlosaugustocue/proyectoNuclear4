<?php

declare(strict_types=1);

namespace App\Domains\Comparacion\Infrastructure\Persistence;

use App\Domains\Comparacion\Domain\Repositories\ModeloLecturaComparacion;
use App\Domains\Comparacion\Domain\ValueObjects\FilaComparacion;
use App\Domains\Compartido\Domain\ValueObjects\Coordenadas;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class PgModeloLecturaComparacion implements ModeloLecturaComparacion
{
    private const TABLA = 'comparacion_vigente';

    public function obtener(string $presentacionId, string $establecimientoId): ?FilaComparacion
    {
        $fila = DB::table(self::TABLA)
            ->where('presentacion_id', $presentacionId)
            ->where('establecimiento_id', $establecimientoId)
            ->first();

        return $fila === null ? null : self::desde((object) get_object_vars($fila));
    }

    public function guardar(FilaComparacion $fila, Coordenadas $ubicacionEstablecimiento): void
    {
        DB::statement(
            'INSERT INTO comparacion_vigente (
                presentacion_id, establecimiento_id, producto_id, observacion_id, precio, precio_por_unidad,
                unidad_base, observado_en, fuente, confirmaciones, estado_confianza, tipo_vigencia,
                establecimiento, presentacion, establecimiento_municipio, ubicacion, actualizado_en)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?::jsonb, ?::jsonb, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, now())
             ON CONFLICT (presentacion_id, establecimiento_id) DO UPDATE SET
                producto_id = EXCLUDED.producto_id, observacion_id = EXCLUDED.observacion_id,
                precio = EXCLUDED.precio, precio_por_unidad = EXCLUDED.precio_por_unidad,
                unidad_base = EXCLUDED.unidad_base, observado_en = EXCLUDED.observado_en,
                fuente = EXCLUDED.fuente, confirmaciones = EXCLUDED.confirmaciones,
                estado_confianza = EXCLUDED.estado_confianza, tipo_vigencia = EXCLUDED.tipo_vigencia,
                establecimiento = EXCLUDED.establecimiento, presentacion = EXCLUDED.presentacion,
                establecimiento_municipio = EXCLUDED.establecimiento_municipio,
                ubicacion = EXCLUDED.ubicacion, actualizado_en = now()',
            [
                $fila->presentacion['id'], $fila->establecimiento['id'], $fila->productoId, $fila->observacionId,
                $fila->precio, $fila->precioPorUnidad, $fila->unidadBase, $fila->observadoEn->format(DATE_ATOM),
                $fila->fuente, $fila->confirmaciones, $fila->estadoConfianza, $fila->tipoVigencia,
                json_encode($fila->establecimiento, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                json_encode($fila->presentacion, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                $fila->establecimiento['municipio'],
                $ubicacionEstablecimiento->longitud(), $ubicacionEstablecimiento->latitud(),
            ],
        );
    }

    public function deProductos(
        array $productoIds,
        ?string $presentacionId = null,
        ?string $municipio = null,
        ?Coordenadas $cerca = null,
        ?float $radioKm = null,
    ): array {
        if ($productoIds === []) {
            return [];
        }

        $consulta = DB::table(self::TABLA)->whereIn('producto_id', $productoIds)->select('*');

        if ($presentacionId !== null) {
            $consulta->where('presentacion_id', $presentacionId);
        }
        if ($municipio !== null) {
            $consulta->whereRaw('unaccent(lower(establecimiento_municipio)) = unaccent(lower(?))', [$municipio]);
        }
        if ($cerca !== null) {
            $lon = $cerca->longitud();
            $lat = $cerca->latitud();
            $consulta->selectRaw(
                'ST_Distance(ubicacion, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) / 1000 AS distancia_km',
                [$lon, $lat],
            );
            if ($radioKm !== null) {
                $consulta->whereRaw('ST_DWithin(ubicacion, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)', [$lon, $lat, $radioKm * 1000]);
            }
        }

        return array_values($consulta->get()->map(static fn (\stdClass $f): FilaComparacion => self::desde($f))->all());
    }

    private static function desde(\stdClass $f): FilaComparacion
    {
        /** @var array{id: string, nombre: string, tipo: string, barrio: ?string, municipio: string} $establecimiento */
        $establecimiento = json_decode((string) $f->establecimiento, true, 512, JSON_THROW_ON_ERROR);
        /** @var array{id: string, descripcion: ?string, cantidad: string, unidad: string, unidadesPorEmpaque: int, unidadBase: string, ean: ?string} $presentacion */
        $presentacion = json_decode((string) $f->presentacion, true, 512, JSON_THROW_ON_ERROR);

        return new FilaComparacion(
            productoId:      (string) $f->producto_id,
            observacionId:   (string) $f->observacion_id,
            precio:          (string) $f->precio,
            precioPorUnidad: (string) $f->precio_por_unidad,
            unidadBase:      (string) $f->unidad_base,
            observadoEn:     new DateTimeImmutable((string) $f->observado_en),
            fuente:          (string) $f->fuente,
            confirmaciones:  (int) $f->confirmaciones,
            estadoConfianza: (string) $f->estado_confianza,
            tipoVigencia:    (string) $f->tipo_vigencia,
            establecimiento: $establecimiento,
            presentacion:    $presentacion,
            distanciaKm:     property_exists($f, 'distancia_km') ? (float) $f->distancia_km : null,
        );
    }
}
