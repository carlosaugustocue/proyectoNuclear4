<?php

declare(strict_types=1);

namespace App\Domains\Establecimientos\Infrastructure\Persistence;

use App\Domains\Compartido\Domain\ValueObjects\Coordenadas;
use App\Domains\Establecimientos\Domain\Repositories\DirectorioLectura;
use App\Domains\Establecimientos\Domain\ValueObjects\EstablecimientoPublico;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class PgDirectorioLectura implements DirectorioLectura
{
    private const COLUMNAS = 'id, nombre, tipo, direccion, barrio, municipio, estado_directorio, '
        . 'ST_Y(ubicacion::geometry) AS lat, ST_X(ubicacion::geometry) AS lon';

    public function buscar(
        ?string $texto,
        ?string $municipio,
        ?Coordenadas $cerca,
        float $radioKm,
        int $pagina,
        int $tamano,
    ): array {
        $consulta = DB::table('establecimientos')->where('estado_directorio', 'APROBADO');

        if ($texto !== null) {
            $patron = '%' . mb_strtolower($texto) . '%';
            $consulta->where(function (Builder $q) use ($patron): void {
                $q->whereRaw('unaccent(lower(nombre)) LIKE unaccent(?)', [$patron])
                  ->orWhereRaw("unaccent(lower(coalesce(barrio, ''))) LIKE unaccent(?)", [$patron]);
            });
        }

        if ($municipio !== null) {
            $consulta->whereRaw('unaccent(lower(municipio)) = unaccent(lower(?))', [$municipio]);
        }

        $columnas = self::COLUMNAS;
        if ($cerca !== null) {
            $punto = 'ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography';
            $consulta->whereRaw("ST_DWithin(ubicacion, {$punto}, ?)", [$cerca->longitud(), $cerca->latitud(), $radioKm * 1000]);
            $columnas .= ", ST_Distance(ubicacion, ST_SetSRID(ST_MakePoint({$cerca->longitud()}, {$cerca->latitud()}), 4326)::geography) / 1000 AS distancia_km";
            $consulta->orderByRaw('distancia_km');
        } else {
            $consulta->orderBy('nombre');
        }

        $total = (clone $consulta)->count();
        $filas = $consulta->selectRaw($columnas)->offset(($pagina - 1) * $tamano)->limit($tamano)->get();

        return [
            'items' => array_values($filas->map(fn (\stdClass $f): EstablecimientoPublico => self::desde($f))->all()),
            'total' => $total,
        ];
    }

    public function porId(string $id): ?EstablecimientoPublico
    {
        $fila = DB::table('establecimientos')->where('id', $id)->selectRaw(self::COLUMNAS)->first();

        return $fila === null ? null : self::desde(self::fila($fila));
    }

    public function distanciasKm(Coordenadas $desde, array $ids): array
    {
        $filas = DB::table('establecimientos')
            ->whereIn('id', $ids)
            ->selectRaw(
                'id, ST_Distance(ubicacion, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) / 1000 AS km',
                [$desde->longitud(), $desde->latitud()],
            )
            ->get();

        $resultado = [];
        foreach ($filas as $f) {
            $resultado[(string) $f->id] = (float) $f->km;
        }

        return $resultado;
    }

    private static function desde(\stdClass $f): EstablecimientoPublico
    {
        return new EstablecimientoPublico(
            id:               (string) $f->id,
            nombre:           (string) $f->nombre,
            tipo:             (string) $f->tipo,
            direccion:        $f->direccion === null ? null : (string) $f->direccion,
            barrio:           $f->barrio === null ? null : (string) $f->barrio,
            municipio:        (string) $f->municipio,
            lat:              round((float) $f->lat, 6),
            lon:              round((float) $f->lon, 6),
            estadoDirectorio: (string) $f->estado_directorio,
            distanciaKm:      property_exists($f, 'distancia_km') ? (float) $f->distancia_km : null,
        );
    }

    /** Normaliza el resultado de first() (object) a stdClass para el mapeo tipado. */
    private static function fila(object $fila): \stdClass
    {
        return (object) get_object_vars($fila);
    }
}
