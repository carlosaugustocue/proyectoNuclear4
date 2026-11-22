<?php

declare(strict_types=1);

use Database\Seeders\SemillaDemostracionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Migración de datos: aplica las fotos de la semilla a bases ya sembradas (staging y producción),
 * para no tener que volver a sembrar. En una base vacía no hace nada; ahí la semilla las asigna.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (SemillaDemostracionSeeder::IMAGENES as [$nombre, $marca]) {
            DB::table('productos')->where('nombre', $nombre)->where('marca', $marca)->whereNull('imagen_url')
                ->get(['id'])
                ->each(fn (object $p) => SemillaDemostracionSeeder::aplicarImagen((string) $p->id, $nombre, $marca));
        }
    }

    public function down(): void
    {
        // Las columnas se eliminan en la migración anterior; aquí no hay nada que revertir.
    }
};
