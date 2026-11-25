<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Ingesta\Application\IngerirSipsa;
use Generator;
use Illuminate\Console\Command;
use SplFileObject;

/**
 * Carga la serie de precios mayoristas de SIPSA-DANE (RF-25, RF-26, ADR-0010). Idempotente.
 * Por defecto usa el CSV versionado de Armenia, extraído con scripts/sipsa/extraer_armenia.py.
 *
 * Fuente: Departamento Administrativo Nacional de Estadística [DANE]. (2026). Sistema de Información de
 * Precios y Abastecimiento del Sector Agropecuario (SIPSA): serie histórica de precios mayoristas 2026
 * [Conjunto de datos].
 */
final class IngerirSipsaCommand extends Command
{
    protected $signature = 'ingesta:sipsa {archivo? : CSV con las columnas fecha,grupo,producto,codigo_cpc,mercado,precio}';

    protected $description = 'Carga el catálogo y la referencia mayorista de SIPSA-DANE (idempotente)';

    private const ARCHIVO_POR_DEFECTO = 'database/data/sipsa/sipsa-mayorista-armenia-2026.csv';

    private const COLUMNAS = ['fecha', 'grupo', 'producto', 'codigo_cpc', 'mercado', 'precio'];

    public function handle(IngerirSipsa $ingesta): int
    {
        $ruta = $this->argument('archivo');
        $ruta = is_string($ruta) && $ruta !== '' ? $ruta : base_path(self::ARCHIVO_POR_DEFECTO);
        if (! is_readable($ruta)) {
            $this->error("No se puede leer el archivo {$ruta}");

            return self::FAILURE;
        }

        $archivo = new SplFileObject($ruta);
        $encabezado = array_map(static fn ($c): string => trim((string) $c, " \t\n\r\0\x0B\u{FEFF}"), (array) $archivo->fgetcsv());
        $faltantes = array_diff(self::COLUMNAS, $encabezado);
        if ($faltantes !== []) {
            $this->error('Al archivo le faltan columnas: '.implode(', ', $faltantes));

            return self::FAILURE;
        }

        $r = $ingesta->ejecutar($this->filas($archivo, $encabezado));

        $this->info(sprintf(
            'SIPSA: %d productos nuevos, %d ya existían, %d referencias mensuales, %d filas rechazadas',
            $r['productosNuevos'], $r['productosExistentes'], $r['referencias'], count($r['rechazadas']),
        ));
        foreach ($r['rechazadas'] as $rechazo) {
            $this->warn("  línea {$rechazo['linea']}: {$rechazo['motivo']}");
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string> $encabezado
     * @return Generator<int, array<string, string|null>>
     */
    private function filas(SplFileObject $archivo, array $encabezado): Generator
    {
        $linea = 1;
        while (! $archivo->eof()) {
            $linea++;
            $valores = $archivo->fgetcsv();
            if (! is_array($valores) || $valores === [null]) {
                continue;
            }
            $fila = [];
            foreach ($encabezado as $i => $columna) {
                $fila[$columna] = isset($valores[$i]) ? (string) $valores[$i] : null;
            }
            yield $linea => array_filter($fila, static fn ($v): bool => $v !== null);
        }
    }
}
