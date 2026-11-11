<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Habilitar la extensión PostGIS si aún no está activa
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('registros_precio', function (Blueprint $table) {
            // Identidad
            $table->uuid('id')->primary();

            // Relaciones (FK diferidas — los módulos Catálogo y Establecimientos
            // se crearán en iteraciones siguientes)
            $table->string('producto_id', 36)->index();
            $table->string('establecimiento_id', 36)->index();

            // Precio (brick/money: monto en texto + código ISO 4217)
            $table->decimal('monto', 15, 2);
            $table->char('moneda', 3)->default('COP');

            // Ubicación geoespacial — PostGIS geography(Point, SRID 4326)
            // Se agrega con SQL crudo porque Laravel Blueprint no soporta geography
            // índice GIST para consultas de proximidad

            // Auditoría
            $table->string('reportado_por', 36)->index();
            $table->timestampTz('registrado_en')->useCurrent();

            // Soft-delete para moderación posterior
            $table->timestampTz('deleted_at')->nullable();
        });

        // Columna geography y su índice GIST (requieren SQL nativo)
        DB::statement(
            'ALTER TABLE registros_precio ADD COLUMN ubicacion geography(Point,4326) NOT NULL'
        );
        DB::statement(
            'CREATE INDEX registros_precio_ubicacion_gist ON registros_precio USING GIST (ubicacion)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_precio');
    }
};
