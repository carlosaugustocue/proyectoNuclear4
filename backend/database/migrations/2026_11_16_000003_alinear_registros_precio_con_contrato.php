<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alinea registros_precio con el contrato ReportePrecioNuevo (openapi.yaml):
 *  - la observación referencia una PRESENTACIÓN (RN-01 necesita cantidad y unidad);
 *  - se elimina `ubicacion`: guardaba la posición del dispositivo, lo que contradice
 *    la privacidad por diseño (guía §12, R-01, EC-07). La ubicación pública es la
 *    del establecimiento;
 *  - `observado_en` conserva el momento real de la observación (modo offline, EC-09);
 *  - `clave_idempotencia` única evita duplicados al sincronizar (ADR-0006).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS registros_precio_ubicacion_gist');

        Schema::table('registros_precio', function (Blueprint $table) {
            $table->dropColumn('ubicacion');
            $table->string('presentacion_id', 36)->nullable()->after('producto_id')->index();
            $table->timestampTz('observado_en')->nullable()->after('reportado_por');
            $table->uuid('clave_idempotencia')->nullable()->unique();
            $table->string('fuente', 16)->default('REPORTANTE');
            $table->index(['reportado_por', 'registrado_en']);
            $table->index(['presentacion_id', 'establecimiento_id', 'observado_en']);
        });
    }

    public function down(): void
    {
        Schema::table('registros_precio', function (Blueprint $table) {
            $table->dropIndex(['presentacion_id', 'establecimiento_id', 'observado_en']);
            $table->dropIndex(['reportado_por', 'registrado_en']);
            $table->dropColumn(['presentacion_id', 'observado_en', 'clave_idempotencia', 'fuente']);
        });

        DB::statement('ALTER TABLE registros_precio ADD COLUMN ubicacion geography(Point,4326)');
    }
};
