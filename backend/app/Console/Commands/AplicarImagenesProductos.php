<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use SplFileObject;

/**
 * Asigna a los productos sin foto las fotos con licencia libre que viajan con la PWA
 * (frontend/public/productos/, servidas por el contenedor web en /productos/). Cada fila del CSV
 * nombra el producto, el archivo y el crédito (autor y licencia), que la PWA muestra bajo la foto.
 *
 * Idempotente y conservador: nunca reemplaza una foto existente (p. ej., la de Open Food Facts) y
 * no aplica nada si alguna fila es inválida. Corre al arrancar la API, después de la ingesta de SIPSA.
 */
final class AplicarImagenesProductos extends Command
{
    protected $signature = 'catalogo:imagenes {archivo? : CSV con las columnas producto,archivo,credito}';

    protected $description = 'Asigna las fotos empacadas con la PWA a los productos que no tienen foto (idempotente)';

    private const ARCHIVO_POR_DEFECTO = 'database/data/imagenes/imagenes-productos.csv';

    private const PATRON_ARCHIVO = '/^[a-z0-9-]+\.webp$/';

    private const MAX_CREDITO = 160;

    public function handle(): int
    {
        $ruta = $this->argument('archivo');
        $ruta = is_string($ruta) && $ruta !== '' ? $ruta : base_path(self::ARCHIVO_POR_DEFECTO);
        if (! is_readable($ruta)) {
            $this->error("No se puede leer el archivo {$ruta}");

            return self::FAILURE;
        }

        [$filas, $errores] = $this->leer($ruta);
        if ($errores !== []) {
            foreach ($errores as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $asignadas = 0;
        foreach ($filas as ['producto' => $producto, 'archivo' => $archivo, 'credito' => $credito]) {
            $asignadas += DB::table('productos')
                ->where('nombre', $producto)
                ->whereNull('imagen_url')
                ->update(['imagen_url' => '/productos/'.$archivo, 'imagen_credito' => $credito, 'updated_at' => now()]);
        }

        $this->info(sprintf('Fotos: %d productos actualizados con %d filas', $asignadas, count($filas)));

        return self::SUCCESS;
    }

    /**
     * @return array{0: list<array{producto: string, archivo: string, credito: string}>, 1: list<string>}
     */
    private function leer(string $ruta): array
    {
        $archivo = new SplFileObject($ruta);
        $archivo->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);
        $filas = [];
        $errores = [];
        foreach ($archivo as $numero => $columnas) {
            if ($numero === 0 || ! is_array($columnas) || $columnas === [null]) {
                continue;
            }
            [$producto, $nombreArchivo, $credito] = array_map(static fn ($v): string => trim((string) $v), array_pad($columnas, 3, ''));
            $linea = (int) $numero + 1;
            if ($producto === '' || preg_match(self::PATRON_ARCHIVO, $nombreArchivo) !== 1) {
                $errores[] = "línea {$linea}: archivo inválido «{$nombreArchivo}» (se espera nombre-en-minusculas.webp)";

                continue;
            }
            if ($credito === '' || mb_strlen($credito) > self::MAX_CREDITO) {
                $errores[] = "línea {$linea}: el crédito es obligatorio y tiene máximo ".self::MAX_CREDITO.' caracteres';

                continue;
            }
            $filas[] = ['producto' => $producto, 'archivo' => $nombreArchivo, 'credito' => $credito];
        }

        return [$filas, $errores];
    }
}
