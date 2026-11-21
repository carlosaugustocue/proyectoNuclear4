<?php

declare(strict_types=1);

namespace App\Domains\Comparacion\Application;

use App\Domains\Catalogo\Application\ConsultarCatalogo;
use App\Domains\Comparacion\Domain\Repositories\ModeloLecturaComparacion;
use App\Domains\Comparacion\Domain\Services\OrdenComparacion;
use App\Domains\Comparacion\Domain\Services\RedactorAdvertencia;
use App\Domains\Comparacion\Domain\ValueObjects\FilaComparacion;
use App\Domains\Comparacion\Domain\ValueObjects\ItemComparacion;
use App\Domains\Compartido\Domain\Services\Comparabilidad;
use App\Domains\Compartido\Domain\ValueObjects\Coordenadas;
use App\Domains\Confianza\Domain\Services\PoliticaVigencia;
use App\Domains\Confianza\Domain\Services\SelectorPrecioVigente;
use App\Domains\Confianza\Domain\ValueObjects\CandidatoPrecio;
use App\Domains\Confianza\Domain\ValueObjects\EstadoVigencia;
use App\Domains\Confianza\Domain\ValueObjects\ResultadoSelector;
use Brick\Math\BigDecimal;
use DateTimeImmutable;

/**
 * Consulta de comparación (RF-13 a RF-17). Lee solo el modelo de lectura y aplica en
 * tiempo de consulta la vigencia (RN-04), la selección del precio mostrado (RN-09) y el
 * orden neutral (RN-10). Ningún atributo comercial interviene (ADR-0009, AR-02).
 */
final class ConsultarComparacion
{
    public const ORDEN_PRECIO    = 'precio_unidad';
    public const ORDEN_DISTANCIA = 'distancia';

    private const NOMBRE_UNIDAD = ['KG' => 'kilogramo', 'L' => 'litro', 'UNIDAD' => 'unidad'];
    private const NORMALIZADOR  = ['KG' => 'kg', 'L' => 'L', 'UNIDAD' => 'unidad'];

    public function __construct(
        private readonly ModeloLecturaComparacion $modelo,
        private readonly ConsultarCatalogo        $catalogo,
        private readonly PromocionesVigentes      $promociones = new SinPromociones(),
    ) {}

    /** @return array<string, mixed>|null ResultadoComparacion del contrato; null si el producto no existe */
    public function comparar(
        string $productoId,
        ?string $presentacionId,
        ?string $municipio,
        ?Coordenadas $cerca,
        ?float $radioKm,
        string $orden,
        bool $incluirNoVigentes,
    ): ?array {
        $producto = $this->catalogo->resumen($productoId);
        if ($producto === null) {
            return null;
        }

        $filas = $this->modelo->deProductos([$productoId], $presentacionId, $municipio, $cerca, $cerca === null ? null : $radioKm);
        $unidadBase = self::unidadBaseDominante($filas, $this->catalogo->detalle($productoId));
        $evaluadas  = $this->evaluar($filas, $unidadBase, $incluirNoVigentes);
        $ordenadas  = $this->ordenar($evaluadas, $orden);

        $items = [];
        foreach ($ordenadas as $i => $evaluada) {
            $items[] = self::item($i + 1, $evaluada['fila'], $evaluada['vigente'], $evaluada['advertencia']);
        }

        return [
            'producto'      => $producto,
            'criterioOrden' => [
                'campo'       => $orden,
                'descripcion' => self::descripcionOrden($orden, $unidadBase),
            ],
            'unidadBase'           => $unidadBase,
            'generadoEn'           => (new DateTimeImmutable())->format(DATE_ATOM),
            'items'                => $items,
            'contenidoPromocional' => $this->promociones->deProductos([$productoId]), // siempre separado de items (ADR-0009, ADR-0023)
        ];
    }

    /**
     * Resumen para tarjetas de producto: menor precio vigente y número de establecimientos.
     *
     * @param  list<string> $productoIds
     * @return list<array{productoId: string, unidadBase: ?string, precioPorUnidadMin: ?string, precioMin: ?string, establecimientos: int}>
     */
    public function resumen(array $productoIds): array
    {
        $porProducto = [];
        foreach ($this->modelo->deProductos($productoIds) as $fila) {
            $porProducto[$fila->productoId][] = $fila;
        }

        $resultado = [];
        foreach ($productoIds as $id) {
            $filas = $porProducto[$id] ?? [];
            $base  = $filas === [] ? null : self::unidadBaseDominante($filas, null);
            $vigentes = $base === null ? [] : array_column($this->evaluar($filas, $base, false), 'fila');

            $resultado[] = [
                'productoId'         => $id,
                'unidadBase'         => $base,
                'precioPorUnidadMin' => self::minimo(array_map(static fn (FilaComparacion $f): string => $f->precioPorUnidad, $vigentes)),
                'precioMin'          => self::minimo(array_map(static fn (FilaComparacion $f): string => $f->precio, $vigentes)),
                'establecimientos'   => count(array_unique(array_map(static fn (FilaComparacion $f): string => $f->establecimiento['id'], $vigentes))),
            ];
        }

        return $resultado;
    }

    /**
     * @param  list<FilaComparacion> $filas
     * @return list<array{fila: FilaComparacion, vigente: bool, advertencia: ?string}>
     */
    private function evaluar(array $filas, string $unidadBase, bool $incluirNoVigentes): array
    {
        $ahora = new DateTimeImmutable();
        $resultado = [];

        foreach ($filas as $fila) {
            // RN-03: solo se comparan presentaciones con la misma unidad base
            if (! Comparabilidad::sonComparables(self::NORMALIZADOR[$fila->unidadBase] ?? '', self::NORMALIZADOR[$unidadBase] ?? '')) {
                continue;
            }

            $confianza = Estados::confianza($fila->estadoConfianza);
            $vigencia  = PoliticaVigencia::evaluar($fila->observadoEn, Estados::categoria($fila->tipoVigencia));
            $vigente   = SelectorPrecioVigente::seleccionar(new CandidatoPrecio($fila->precio, $confianza, $vigencia))
                !== ResultadoSelector::NO_MOSTRAR;

            if (! $vigente && ! $incluirNoVigentes) {
                continue;
            }

            $dias = (int) $ahora->diff($fila->observadoEn)->days;
            $resultado[] = [
                'fila'        => $fila,
                'vigente'     => $vigente,
                'advertencia' => RedactorAdvertencia::redactar($vigencia, $confianza, $dias),
            ];
        }

        return $resultado;
    }

    /**
     * @param  list<array{fila: FilaComparacion, vigente: bool, advertencia: ?string}> $evaluadas
     * @return list<array{fila: FilaComparacion, vigente: bool, advertencia: ?string}>
     */
    private function ordenar(array $evaluadas, string $orden): array
    {
        $porItem = [];
        $items   = [];
        foreach ($evaluadas as $evaluada) {
            $f    = $evaluada['fila'];
            $item = new ItemComparacion(
                establecimientoId: $f->establecimiento['id'],
                monto:             $f->precioPorUnidad,
                cantidad:          '1',
                unidad:            self::NORMALIZADOR[$f->unidadBase] ?? 'unidad',
                registradoEn:      $f->observadoEn,
                atributoComercial: null,
                distanciaMetros:   $f->distanciaKm === null ? null : $f->distanciaKm * 1000,
            );
            $porItem[spl_object_id($item)] = $evaluada;
            $items[] = $item;
        }

        $ordenados = $orden === self::ORDEN_DISTANCIA
            ? OrdenComparacion::porDistancia($items)
            : OrdenComparacion::porPrecioUnitario($items);

        return array_map(static fn (ItemComparacion $i): array => $porItem[spl_object_id($i)], $ordenados);
    }

    /** @return array<string, mixed> ItemComparacion del contrato */
    private static function item(int $posicion, FilaComparacion $f, bool $vigente, ?string $advertencia): array
    {
        $establecimiento = [
            'id'     => $f->establecimiento['id'],
            'nombre' => $f->establecimiento['nombre'],
            'tipo'   => $f->establecimiento['tipo'],
            'barrio' => $f->establecimiento['barrio'],
        ];
        if ($f->distanciaKm !== null) {
            $establecimiento['distanciaKm'] = round($f->distanciaKm, 2);
        }

        return [
            'posicion'        => $posicion,
            'establecimiento' => $establecimiento,
            'presentacion'    => $f->presentacion,
            'precio'          => $f->precio,
            'precioPorUnidad' => $f->precioPorUnidad,
            'observadoEn'     => $f->observadoEn->format(DATE_ATOM),
            'fuente'          => $f->fuente,
            'confirmaciones'  => $f->confirmaciones,
            'estadoConfianza' => $f->estadoConfianza,
            'vigente'         => $vigente,
            'advertencia'     => $advertencia,
        ];
    }

    /**
     * @param list<FilaComparacion>     $filas
     * @param array<string, mixed>|null $producto
     */
    private static function unidadBaseDominante(array $filas, ?array $producto): string
    {
        $conteo = [];
        foreach ($filas as $f) {
            $conteo[$f->unidadBase] = ($conteo[$f->unidadBase] ?? 0) + 1;
        }
        if ($conteo !== []) {
            arsort($conteo);

            return (string) array_key_first($conteo);
        }

        $presentaciones = is_array($producto['presentaciones'] ?? null) ? $producto['presentaciones'] : [];
        $primera = $presentaciones[0] ?? null;

        return is_array($primera) && is_string($primera['unidadBase'] ?? null) ? $primera['unidadBase'] : 'UNIDAD';
    }

    private static function descripcionOrden(string $orden, string $unidadBase): string
    {
        $neutralidad = 'Ningún comercio paga por su posición.';

        if ($orden === self::ORDEN_DISTANCIA) {
            return "Ordenado por distancia desde la ubicación indicada, de menor a mayor. {$neutralidad}";
        }

        $unidad = self::NOMBRE_UNIDAD[$unidadBase] ?? 'unidad';

        return "Ordenado por precio por {$unidad}, de menor a mayor; ante un empate, primero el reporte más reciente. {$neutralidad}";
    }

    /** @param list<string> $valores */
    private static function minimo(array $valores): ?string
    {
        $min = null;
        foreach ($valores as $v) {
            if ($min === null || BigDecimal::of($v)->isLessThan(BigDecimal::of($min))) {
                $min = $v;
            }
        }

        return $min === null ? null : (string) BigDecimal::of($min)->toScale(2);
    }
}
