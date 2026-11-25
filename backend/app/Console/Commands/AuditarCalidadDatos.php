<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Comparacion\Application\ConsultarComparacion;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Auditoría de calidad de los datos (ISO/IEC 25012), instrumento de la evaluación ISO/IEC 25040 (Cal. E5).
 *
 * Es de SOLO LECTURA: se puede ejecutar en staging y producción. Medidas:
 *  - D-01 Actualidad · M-15: precios mostrados como vigentes fuera de su plazo (RN-04) sin advertencia. Recorre
 *    la comparación real (ConsultarComparacion), es decir, lo que ve el consumidor, y calcula la antigüedad con
 *    un oráculo propio (días completos frente a 3 o 7 días) para no repetir la lógica que audita.
 *  - D-02 Completitud: presentaciones con cantidad > 0, unidad, unidad base y unidades por empaque válidas.
 *  - D-03 Trazabilidad: observaciones con fuente, fecha observada y fecha de registro.
 *  - D-04 Consistencia: productos duplicados por nombre y marca normalizados (minúsculas, sin tildes ni espacios
 *    sobrantes); cuenta los sobrantes de cada grupo.
 *  - D-05 Precios oficiales no confirmados · M-22 (ADR-0015).
 *  - D-06 Confidencialidad (EC-07, R-01): columnas del esquema que podrían guardar la ubicación o los rastros
 *    del dispositivo de un usuario (coordenadas fuera del directorio de establecimientos, IP, user agent).
 */
final class AuditarCalidadDatos extends Command
{
    protected $signature = 'calidad:auditar-datos {--salida= : ruta del archivo JSON con los resultados}';

    protected $description = 'Audita la calidad de los datos (ISO/IEC 25012): D-01…D-06, M-15 y M-22. Solo lectura.';

    /** Plazo de vigencia en días por tipo de producto (RN-04, especificación §5). */
    private const PLAZO_DIAS = ['PERECEDERO' => 3, 'ABARROTE' => 7];

    private const UNIDADES = ['G', 'KG', 'ML', 'L', 'UNIDAD'];
    private const UNIDADES_BASE = ['KG', 'L', 'UNIDAD'];

    /** Tablas que sí deben tener coordenadas: el directorio público de establecimientos y su proyección. */
    private const TABLAS_CON_UBICACION_PERMITIDA = ['establecimientos', 'comparacion_vigente'];

    private const PATRON_RASTRO_DISPOSITIVO = '^(ip|ip_address|direccion_ip|user_agent|agente_usuario|latitud|longitud|lat|lon|lng)$';

    public function handle(ConsultarComparacion $comparacion): int
    {
        $medidas = [
            $this->actualidad($comparacion),
            $this->completitud(),
            $this->trazabilidad(),
            $this->consistencia(),
            $this->preciosOficialesNoConfirmados(),
            $this->confidencialidad(),
        ];

        $this->table(
            ['Id', 'Medida', 'Característica', 'Valor', 'Umbral', 'Cumple'],
            array_map(static fn (array $m): array => [
                $m['id'], $m['medida'] ?? '—', $m['caracteristica'],
                $m['valor'] === null ? 'sin datos' : sprintf('%d/%d = %s', $m['numerador'], $m['denominador'], $m['valor']),
                $m['umbral'], $m['cumple'] === null ? '—' : ($m['cumple'] ? 'sí' : 'NO'),
            ], $medidas),
        );

        $salida = $this->option('salida');
        if (is_string($salida) && $salida !== '') {
            $informe = ['generadoEn' => (new DateTimeImmutable())->format(DATE_ATOM), 'medidas' => $medidas];
            file_put_contents($salida, json_encode($informe, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->info("Resultados en {$salida}");
        }

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function actualidad(ConsultarComparacion $comparacion): array
    {
        $ahora = new DateTimeImmutable();
        $mostrados = 0;
        $violaciones = 0;
        $conAdvertencia = 0;

        $productos = DB::table('productos')->where('estado_catalogo', 'APROBADO')->pluck('tipo_vigencia', 'id');
        foreach ($productos as $productoId => $tipoVigencia) {
            $resultado = $comparacion->comparar((string) $productoId, null, null, null, null, 'precio_unidad', false);
            $plazo = self::PLAZO_DIAS[(string) $tipoVigencia] ?? self::PLAZO_DIAS['ABARROTE'];

            foreach ($resultado['items'] ?? [] as $item) {
                $mostrados++;
                $dias = $ahora->diff(new DateTimeImmutable($item['observadoEn']))->days;
                if ($item['vigente'] && $dias > $plazo) {
                    $item['advertencia'] === null ? $violaciones++ : $conAdvertencia++;
                }
            }
        }

        return self::medida('D-01', 'M-15', 'Actualidad', $violaciones, $mostrados, '= 0', static fn (float $x): bool => $x == 0.0)
            + ['detalle' => ['fueraDePlazoConAdvertencia' => $conAdvertencia]];
    }

    /** @return array<string, mixed> */
    private function completitud(): array
    {
        $total = DB::table('presentaciones')->count();
        $completas = DB::table('presentaciones')
            ->where('cantidad', '>', 0)
            ->whereIn('unidad', self::UNIDADES)
            ->whereIn('unidad_base', self::UNIDADES_BASE)
            ->where('unidades_por_empaque', '>=', 1)
            ->count();

        return self::medida('D-02', null, 'Completitud', $completas, $total, '= 1', static fn (float $x): bool => $x == 1.0);
    }

    /** @return array<string, mixed> */
    private function trazabilidad(): array
    {
        $base = DB::table('registros_precio')->whereNull('deleted_at');
        $total = (clone $base)->count();
        $trazables = (clone $base)->whereNotNull('fuente')->whereNotNull('observado_en')->whereNotNull('registrado_en')->count();

        return self::medida('D-03', null, 'Trazabilidad', $trazables, $total, '= 1', static fn (float $x): bool => $x == 1.0);
    }

    /** @return array<string, mixed> */
    private function consistencia(): array
    {
        $total = DB::table('productos')->count();
        $sobrantes = (int) DB::selectOne(<<<'SQL'
            SELECT COALESCE(SUM(n - 1), 0) AS sobrantes FROM (
                SELECT COUNT(*) AS n
                FROM productos
                GROUP BY lower(unaccent(btrim(nombre))), lower(unaccent(btrim(COALESCE(marca, ''))))
                HAVING COUNT(*) > 1
            ) AS grupos
        SQL)->sobrantes;

        return self::medida('D-04', null, 'Consistencia', $sobrantes, $total, '≤ 0,02', static fn (float $x): bool => $x <= 0.02);
    }

    /** @return array<string, mixed> */
    private function preciosOficialesNoConfirmados(): array
    {
        $oficiales = DB::table('registros_precio')->where('fuente', 'COMERCIO')->whereNull('deleted_at');
        $total = (clone $oficiales)->count();
        $noConfirmados = (clone $oficiales)
            ->leftJoin('evaluaciones_confianza as e', 'e.observacion_id', '=', 'registros_precio.id')
            ->where(static fn ($q) => $q->whereNull('e.estado')->orWhere('e.estado', '<>', 'CONFIABLE'))
            ->count();

        return self::medida('D-05', 'M-22', 'Credibilidad (precio oficial)', $noConfirmados, $total, '≤ 0,10', static fn (float $x): bool => $x <= 0.10);
    }

    /** @return array<string, mixed> */
    private function confidencialidad(): array
    {
        $columnas = array_map(
            static fn (\stdClass $c): string => "{$c->table_name}.{$c->column_name}",
            DB::select(
                <<<'SQL'
                    SELECT table_name, column_name
                    FROM information_schema.columns
                    WHERE table_schema = current_schema()
                      AND (
                            (udt_name IN ('geography', 'geometry') AND NOT (table_name = ANY (?)))
                         OR column_name ~* ?
                      )
                      AND table_name NOT IN ('spatial_ref_sys', 'geometry_columns', 'geography_columns')
                    ORDER BY table_name, column_name
                SQL,
                ['{'.implode(',', self::TABLAS_CON_UBICACION_PERMITIDA).'}', self::PATRON_RASTRO_DISPOSITIVO],
            ),
        );
        $tablas = (int) DB::selectOne(
            "SELECT COUNT(*) AS n FROM information_schema.tables WHERE table_schema = current_schema() AND table_type = 'BASE TABLE'",
        )->n;

        return self::medida('D-06', 'M-10', 'Confidencialidad', count($columnas), $tablas, '= 0 columnas', static fn (float $x): bool => $x == 0.0)
            + ['detalle' => ['columnas' => $columnas]];
    }

    /**
     * @param  callable(float): bool $cumple
     * @return array<string, mixed>
     */
    private static function medida(string $id, ?string $medida, string $caracteristica, int $numerador, int $denominador, string $umbral, callable $cumple): array
    {
        $valor = $denominador === 0 ? null : round($numerador / $denominador, 4);

        return [
            'id'             => $id,
            'medida'         => $medida,
            'caracteristica' => $caracteristica.' (ISO/IEC 25012)',
            'numerador'      => $numerador,
            'denominador'    => $denominador,
            'valor'          => $valor,
            'umbral'         => $umbral,
            'cumple'         => $valor === null ? null : $cumple($valor),
        ];
    }
}
