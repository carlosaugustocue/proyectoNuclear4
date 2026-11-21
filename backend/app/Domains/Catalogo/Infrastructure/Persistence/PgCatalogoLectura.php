<?php

declare(strict_types=1);

namespace App\Domains\Catalogo\Infrastructure\Persistence;

use App\Domains\Catalogo\Domain\Repositories\CatalogoLectura;
use App\Domains\Catalogo\Domain\ValueObjects\PresentacionCatalogo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class PgCatalogoLectura implements CatalogoLectura
{
    private const COLUMNAS_RESUMEN = ['id', 'nombre', 'marca', 'categoria', 'imagen_url', 'imagen_credito'];

    public function buscar(?string $texto, ?string $ean, ?string $categoria, int $pagina, int $tamano): array
    {
        $consulta = DB::table('productos')->where('estado_catalogo', 'APROBADO');

        if ($texto !== null) {
            $patron = '%' . mb_strtolower($texto) . '%';
            $consulta->where(function (Builder $q) use ($patron): void {
                $q->whereRaw('unaccent(lower(nombre)) LIKE unaccent(?)', [$patron])
                  ->orWhereRaw("unaccent(lower(coalesce(marca, ''))) LIKE unaccent(?)", [$patron])
                  ->orWhereRaw('unaccent(lower(alias::text)) LIKE unaccent(?)', [$patron]);
            });
        }

        if ($ean !== null) {
            $consulta->whereIn('id', DB::table('presentaciones')->select('producto_id')->where('ean', $ean));
        }

        if ($categoria !== null) {
            $consulta->whereRaw('unaccent(lower(categoria)) = unaccent(lower(?))', [$categoria]);
        }

        $total = (clone $consulta)->count();
        $filas = $consulta->orderBy('nombre')
            ->offset(($pagina - 1) * $tamano)
            ->limit($tamano)
            ->get(self::COLUMNAS_RESUMEN);

        return [
            'items' => array_values($filas->map(fn (\stdClass $f): array => self::resumenDe($f))->all()),
            'total' => $total,
        ];
    }

    public function resumen(string $productoId): ?array
    {
        $fila = DB::table('productos')->where('id', $productoId)->first(self::COLUMNAS_RESUMEN);

        return $fila === null ? null : self::resumenDe(self::fila($fila));
    }

    public function presentacionesDe(string $productoId): array
    {
        return array_values(DB::table('presentaciones')
            ->join('productos', 'productos.id', '=', 'presentaciones.producto_id')
            ->where('presentaciones.producto_id', $productoId)
            ->orderBy('presentaciones.cantidad')
            ->get(['presentaciones.*', 'productos.tipo_vigencia'])
            ->map(fn (\stdClass $f): PresentacionCatalogo => self::presentacionDe($f))
            ->all());
    }

    public function aliasDe(string $productoId): array
    {
        $alias = DB::table('productos')->where('id', $productoId)->value('alias');
        $lista = is_string($alias) ? json_decode($alias, true) : [];

        return is_array($lista) ? array_values(array_map('strval', $lista)) : [];
    }

    public function estadoCatalogo(string $productoId): ?string
    {
        $estado = DB::table('productos')->where('id', $productoId)->value('estado_catalogo');

        return is_string($estado) ? $estado : null;
    }

    public function presentacion(string $presentacionId): ?PresentacionCatalogo
    {
        $fila = DB::table('presentaciones')
            ->join('productos', 'productos.id', '=', 'presentaciones.producto_id')
            ->where('presentaciones.id', $presentacionId)
            ->first(['presentaciones.*', 'productos.tipo_vigencia']);

        return $fila === null ? null : self::presentacionDe(self::fila($fila));
    }

    /** @return array{id: string, nombre: string, marca: ?string, categoria: string, imagen: ?array{url: string, credito: ?string}} */
    private static function resumenDe(\stdClass $f): array
    {
        return [
            'id'        => (string) $f->id,
            'nombre'    => (string) $f->nombre,
            'marca'     => $f->marca === null ? null : (string) $f->marca,
            'categoria' => (string) $f->categoria,
            'imagen'    => self::imagenDe($f),
        ];
    }

    /**
     * Solo URLs https: descarta javascript:, data: y http (contenido mixto). Sin foto → null y el
     * cliente muestra la ilustración de la categoría.
     *
     * @return array{url: string, credito: ?string}|null
     */
    private static function imagenDe(\stdClass $f): ?array
    {
        $url = isset($f->imagen_url) ? (string) $f->imagen_url : '';
        if (! str_starts_with($url, 'https://') || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return ['url' => $url, 'credito' => isset($f->imagen_credito) ? (string) $f->imagen_credito : null];
    }

    public function unidadesBase(array $productoIds): array
    {
        if ($productoIds === []) {
            return [];
        }

        // DISTINCT ON: la primera presentación por cantidad de cada producto, igual que presentacionesDe().
        return DB::table('presentaciones')
            ->selectRaw('DISTINCT ON (producto_id) producto_id, unidad_base')
            ->whereIn('producto_id', $productoIds)
            ->orderBy('producto_id')
            ->orderBy('cantidad')
            ->orderBy('id')
            ->pluck('unidad_base', 'producto_id')
            ->map(static fn (mixed $u): string => (string) $u)
            ->all();
    }

    private static function presentacionDe(\stdClass $f): PresentacionCatalogo
    {
        return new PresentacionCatalogo(
            id:                 (string) $f->id,
            productoId:         (string) $f->producto_id,
            descripcion:        $f->descripcion === null ? null : (string) $f->descripcion,
            cantidad:           self::decimalLimpio((string) $f->cantidad),
            unidad:             (string) $f->unidad,
            unidadesPorEmpaque: (int) $f->unidades_por_empaque,
            unidadBase:         (string) $f->unidad_base,
            ean:                $f->ean === null ? null : (string) $f->ean,
            tipoVigencia:       (string) $f->tipo_vigencia,
        );
    }

    /** "500.000" → "500"; "0.454" → "0.454" (formato del contrato: ^[0-9]+(\.[0-9]+)?$) */
    private static function decimalLimpio(string $valor): string
    {
        return str_contains($valor, '.') ? rtrim(rtrim($valor, '0'), '.') : $valor;
    }

    /** Normaliza el resultado de first() (object) a stdClass para el mapeo tipado. */
    private static function fila(object $fila): \stdClass
    {
        return (object) get_object_vars($fila);
    }
}
