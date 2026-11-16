<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Catálogo (ADR-0008): producto canónico + presentaciones.
 * La unidad base de cada presentación habilita la normalización RN-01 y la
 * comparabilidad RN-03; `tipo_vigencia` alimenta la política RN-04.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Búsqueda sin distinción de tildes (RF-01)
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');

        Schema::create('productos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 120);
            $table->string('marca', 60)->nullable();
            $table->string('categoria', 40)->index();
            $table->string('tipo_vigencia', 12)->default('ABARROTE'); // PERECEDERO | ABARROTE
            $table->jsonb('alias')->default('[]');
            $table->string('estado_catalogo', 10)->default('APROBADO'); // APROBADO | PENDIENTE
            $table->timestampsTz();
        });

        Schema::create('presentaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('descripcion', 80)->nullable();
            $table->decimal('cantidad', 12, 3);
            $table->string('unidad', 6);              // G | KG | ML | L | UNIDAD
            $table->unsignedSmallInteger('unidades_por_empaque')->default(1);
            $table->string('unidad_base', 6);         // KG | L | UNIDAD
            $table->string('ean', 14)->nullable()->unique();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presentaciones');
        Schema::dropIfExists('productos');
    }
};
