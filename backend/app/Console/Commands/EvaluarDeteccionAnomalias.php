<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Precios\Application\Commands\RegistrarReportePrecioCommand;
use App\Domains\Precios\Application\Handlers\RegistrarReportePrecioHandler;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * Medición de EC-03 (credibilidad): exhaustividad M-03 y falsos positivos M-04.
 *
 * Construye un conjunto de prueba ETIQUETADO y SINTÉTICO (≥ 200 reportes, 20 % anómalos) y lo pasa por el
 * caso de uso real (RegistrarReportePrecioHandler → Confianza → proyección → Moderación), en orden
 * cronológico. Luego compara el estado que asignó Confianza con la etiqueta conocida de cada reporte.
 *
 * Composición (reproducible con --semilla):
 *  - 20 presentaciones × 10 establecimientos propios (prefijo «[EC-03]»), con un factor de precio fijo por
 *    establecimiento entre 0,92 y 1,08 y un reporte legítimo de calentamiento por par.
 *  - 200 reportes legítimos: 185 con el precio del establecimiento y ruido de ±6 %, y 15 descuentos reales
 *    (×0,82–0,88), que NO deberían marcarse (equidad entre comercios, guía §12.3).
 *  - 50 reportes anómalos (20 %): 20 burdos (×0,25–0,5 o ×2–3,5), 12 moderados (×0,6–0,7 o ×1,4–1,6),
 *    6 sutiles (×0,75–0,82 o ×1,2–1,3) y 12 de un ataque coordinado (4 grupos de 3 cuentas distintas con el
 *    mismo precio falso en la misma tienda).
 *
 * Los grupos «sutil» y «descuento» se solapan a propósito: muestran dónde está la frontera del detector.
 *
 * Detectado = estado POR_VERIFICAR. Cada reporte usa una cuenta distinta, así que el límite RN-08 no interviene.
 */
final class EvaluarDeteccionAnomalias extends Command
{
    protected $signature = 'calidad:deteccion
        {--semilla=2026 : semilla del generador para reproducir el conjunto}
        {--salida= : ruta del archivo JSON con los resultados}';

    protected $description = 'Mide M-03 y M-04 (EC-03) con un conjunto etiquetado sintético';

    private const PRESENTACIONES = 20;
    private const ESTABLECIMIENTOS = 10;
    private const LEGITIMOS = 185;
    private const DESCUENTOS = 15;
    private const BURDOS = 20;
    private const MODERADOS = 12;
    private const SUTILES = 6;
    private const GRUPOS_COORDINADOS = 4;
    private const CUENTAS_POR_GRUPO = 3;

    /** @var array<string, array{legitimo: bool, tipo: string, estado: ?string}> */
    private array $etiquetas = [];

    private int $cuenta = 0;

    public function handle(RegistrarReportePrecioHandler $handler): int
    {
        if (app()->environment('production')) {
            $this->error('Esta medición crea datos sintéticos: no se ejecuta en producción.');

            return self::FAILURE;
        }

        config(['queue.default' => 'sync']); // el flujo de eventos completo, sin worker
        mt_srand((int) $this->option('semilla'));

        $presentaciones   = $this->crearPresentaciones();
        $establecimientos = $this->crearEstablecimientos();
        $inicio           = new DateTimeImmutable('-5 days');

        $this->calentar($handler, $presentaciones, $establecimientos, $inicio);
        $reportes = $this->generarConjunto($presentaciones, $establecimientos);

        $paso = (int) floor((4 * 86400) / count($reportes)); // de -4 días a ~ahora, en orden
        foreach ($reportes as $i => $r) {
            $this->registrar($handler, $r, $inicio->modify('+' . (86400 + $i * $paso) . ' seconds'));
        }

        $resultado = $this->calcular();
        $this->mostrar($resultado);

        $salida = $this->option('salida');
        if (is_string($salida) && $salida !== '') {
            file_put_contents($salida, json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->info("Resultados en {$salida}");
        }

        return self::SUCCESS;
    }

    /** @return list<array{id: string, base: float}> */
    private function crearPresentaciones(): array
    {
        $resultado = [];
        for ($i = 1; $i <= self::PRESENTACIONES; $i++) {
            $productoId = Uuid::uuid7()->toString();
            DB::table('productos')->insert([
                'id' => $productoId, 'nombre' => "[EC-03] Producto {$i}", 'categoria' => 'Granos',
                'tipo_vigencia' => 'ABARROTE', 'alias' => '[]', 'created_at' => now(), 'updated_at' => now(),
            ]);
            $id = Uuid::uuid7()->toString();
            DB::table('presentaciones')->insert([
                'id' => $id, 'producto_id' => $productoId, 'descripcion' => 'Bolsa 1 kg', 'cantidad' => 1,
                'unidad' => 'KG', 'unidades_por_empaque' => 1, 'unidad_base' => 'KG',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $resultado[] = ['id' => $id, 'base' => (float) mt_rand(2000, 30000)];
        }

        return $resultado;
    }

    /** @return list<array{id: string, factor: float}> */
    private function crearEstablecimientos(): array
    {
        $resultado = [];
        for ($i = 1; $i <= self::ESTABLECIMIENTOS; $i++) {
            $id = Uuid::uuid7()->toString();
            $lon = -75.70 + $i * 0.004;
            DB::table('establecimientos')->insert([
                'id' => $id, 'nombre' => "[EC-03] Establecimiento {$i}", 'tipo' => 'TIENDA_BARRIO',
                'municipio' => 'Armenia', 'ubicacion' => DB::raw("ST_GeogFromText('POINT({$lon} 4.53)')"),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $resultado[] = ['id' => $id, 'factor' => $this->uniforme(0.92, 1.08)];
        }

        return $resultado;
    }

    /**
     * Un reporte legítimo por par presentación × establecimiento antes de la ventana evaluada,
     * para que el detector tenga referencia (n ≥ 5). No se cuenta en las medidas.
     *
     * @param list<array{id: string, base: float}>   $presentaciones
     * @param list<array{id: string, factor: float}> $establecimientos
     */
    private function calentar(RegistrarReportePrecioHandler $handler, array $presentaciones, array $establecimientos, DateTimeImmutable $inicio): void
    {
        $segundo = 0;
        foreach ($presentaciones as $p) {
            foreach ($establecimientos as $e) {
                $this->enviar($handler, $p['id'], $e['id'], $p['base'] * $e['factor'], $inicio->modify('+' . ($segundo += 60) . ' seconds'));
            }
        }
    }

    /**
     * @param list<array{id: string, base: float}>   $presentaciones
     * @param list<array{id: string, factor: float}> $establecimientos
     * @return list<array{presentacion: string, establecimiento: string, precio: float, legitimo: bool, tipo: string}>
     */
    private function generarConjunto(array $presentaciones, array $establecimientos): array
    {
        $sueltos = [];
        $elegir = function () use ($presentaciones, $establecimientos): array {
            return [$presentaciones[mt_rand(0, count($presentaciones) - 1)], $establecimientos[mt_rand(0, count($establecimientos) - 1)]];
        };

        for ($i = 0; $i < self::LEGITIMOS; $i++) {
            [$p, $e] = $elegir();
            $sueltos[] = [[$p['id'], $e['id'], $p['base'] * $e['factor'] * $this->uniforme(0.94, 1.06), true, 'legitimo']];
        }
        for ($i = 0; $i < self::DESCUENTOS; $i++) {
            [$p, $e] = $elegir();
            $sueltos[] = [[$p['id'], $e['id'], $p['base'] * $e['factor'] * $this->uniforme(0.82, 0.88), true, 'descuento']];
        }
        for ($i = 0; $i < self::SUTILES; $i++) {
            [$p, $e] = $elegir();
            $factor = mt_rand(0, 1) === 0 ? $this->uniforme(0.75, 0.82) : $this->uniforme(1.2, 1.3);
            $sueltos[] = [[$p['id'], $e['id'], $p['base'] * $e['factor'] * $factor, false, 'sutil']];
        }
        for ($i = 0; $i < self::BURDOS; $i++) {
            [$p, $e] = $elegir();
            $factor = mt_rand(0, 1) === 0 ? $this->uniforme(0.25, 0.5) : $this->uniforme(2.0, 3.5);
            $sueltos[] = [[$p['id'], $e['id'], $p['base'] * $e['factor'] * $factor, false, 'burdo']];
        }
        for ($i = 0; $i < self::MODERADOS; $i++) {
            [$p, $e] = $elegir();
            $factor = mt_rand(0, 1) === 0 ? $this->uniforme(0.6, 0.7) : $this->uniforme(1.4, 1.6);
            $sueltos[] = [[$p['id'], $e['id'], $p['base'] * $e['factor'] * $factor, false, 'moderado']];
        }
        for ($g = 0; $g < self::GRUPOS_COORDINADOS; $g++) {
            [$p, $e] = $elegir();
            $falso = $p['base'] * $e['factor'] * ($g % 2 === 0 ? $this->uniforme(1.8, 2.5) : $this->uniforme(0.4, 0.5));
            $sueltos[] = array_fill(0, self::CUENTAS_POR_GRUPO, [$p['id'], $e['id'], $falso, false, 'coordinado']);
        }

        // Mezcla de Fisher-Yates con la semilla; los reportes de un ataque coordinado quedan seguidos.
        for ($i = count($sueltos) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$sueltos[$i], $sueltos[$j]] = [$sueltos[$j], $sueltos[$i]];
        }

        $reportes = [];
        foreach ($sueltos as $bloque) {
            foreach ($bloque as [$presentacion, $establecimiento, $precio, $legitimo, $tipo]) {
                $reportes[] = compact('presentacion', 'establecimiento', 'precio', 'legitimo', 'tipo');
            }
        }

        return $reportes;
    }

    /** @param array{presentacion: string, establecimiento: string, precio: float, legitimo: bool, tipo: string} $r */
    private function registrar(RegistrarReportePrecioHandler $handler, array $r, DateTimeImmutable $en): void
    {
        $id = $this->enviar($handler, $r['presentacion'], $r['establecimiento'], $r['precio'], $en);
        $this->etiquetas[$id] = ['legitimo' => $r['legitimo'], 'tipo' => $r['tipo'], 'estado' => null];
    }

    private function enviar(RegistrarReportePrecioHandler $handler, string $presentacion, string $establecimiento, float $precio, DateTimeImmutable $en): string
    {
        return $handler->handle(new RegistrarReportePrecioCommand(
            presentacionId:    $presentacion,
            establecimientoId: $establecimiento,
            precio:            number_format(max(50.0, round($precio / 10) * 10), 2, '.', ''),
            observadoEn:       $en,
            reportadoPor:      'ec03-s' . (int) $this->option('semilla') . '-' . (++$this->cuenta),
            claveIdempotencia: Uuid::uuid4()->toString(),
        ))->observacionId;
    }

    /** @return array<string, mixed> */
    private function calcular(): array
    {
        $estados = DB::table('evaluaciones_confianza')->whereIn('observacion_id', array_keys($this->etiquetas))
            ->pluck('estado', 'observacion_id')->all();
        $casos = DB::table('casos_moderacion')->whereIn('observacion_id', array_keys($this->etiquetas))
            ->pluck('observacion_id')->flip()->all();

        $m = ['vp' => 0, 'fn' => 0, 'fp' => 0, 'vn' => 0];
        $porTipo = [];
        $anomalasConCaso = 0;
        foreach ($this->etiquetas as $id => $etiqueta) {
            $detectado = ($estados[$id] ?? null) === 'POR_VERIFICAR';
            $clave = $etiqueta['legitimo'] ? ($detectado ? 'fp' : 'vn') : ($detectado ? 'vp' : 'fn');
            $m[$clave]++;
            $porTipo[$etiqueta['tipo']] ??= ['total' => 0, 'marcados' => 0];
            $porTipo[$etiqueta['tipo']]['total']++;
            $porTipo[$etiqueta['tipo']]['marcados'] += $detectado ? 1 : 0;
            if (! $etiqueta['legitimo'] && isset($casos[$id])) {
                $anomalasConCaso++;
            }
        }

        $anomalos = $m['vp'] + $m['fn'];
        $legitimos = $m['fp'] + $m['vn'];

        return [
            'medicion'      => 'EC-03 · M-03 exhaustividad y M-04 falsos positivos',
            'fecha'         => now()->toIso8601String(),
            'semilla'       => (int) $this->option('semilla'),
            'conjunto'      => ['total' => count($this->etiquetas), 'anomalos' => $anomalos, 'legitimos' => $legitimos, 'sintetico' => true],
            'matriz'        => $m,
            'M-03'          => ['valor' => $anomalos > 0 ? round($m['vp'] / $anomalos, 4) : null, 'aceptable' => '>= 0.80', 'objetivo' => '>= 0.90'],
            'M-04'          => ['valor' => $legitimos > 0 ? round($m['fp'] / $legitimos, 4) : null, 'aceptable' => '<= 0.05', 'objetivo' => '<= 0.03'],
            'por_tipo'      => $porTipo,
            'anomalos_con_caso_de_moderacion' => $anomalasConCaso,
        ];
    }

    /** @param array<string, mixed> $r */
    private function mostrar(array $r): void
    {
        /** @var array{vp: int, fn: int, fp: int, vn: int} $m */
        $m = $r['matriz'];
        $this->table(['', 'Marcado POR_VERIFICAR', 'No marcado'], [
            ['Anómalo (etiqueta)', $m['vp'], $m['fn']],
            ['Legítimo (etiqueta)', $m['fp'], $m['vn']],
        ]);
        /** @var array{valor: ?float} $m03 */
        $m03 = $r['M-03'];
        /** @var array{valor: ?float} $m04 */
        $m04 = $r['M-04'];
        $this->line(sprintf('M-03 exhaustividad = %s (aceptable ≥ 0,80)', var_export($m03['valor'], true)));
        $this->line(sprintf('M-04 falsos positivos = %s (aceptable ≤ 0,05)', var_export($m04['valor'], true)));
    }

    private function uniforme(float $min, float $max): float
    {
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    }
}
