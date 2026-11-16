<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Establecimientos (ADR-0005): directorio con ubicación PostGIS.
 * La ubicación es la del establecimiento (dato público), nunca la del reportante.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('establecimientos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 120);
            $table->string('tipo', 16);               // TIENDA_BARRIO | MINIMERCADO | SUPERMERCADO
            $table->string('direccion', 120)->nullable();
            $table->string('barrio', 60)->nullable();
            $table->string('municipio', 60)->index();
            $table->string('estado_directorio', 10)->default('APROBADO');
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE establecimientos ADD COLUMN ubicacion geography(Point,4326) NOT NULL');
        DB::statement('CREATE INDEX establecimientos_ubicacion_gist ON establecimientos USING GIST (ubicacion)');
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimientos');
    }
};
