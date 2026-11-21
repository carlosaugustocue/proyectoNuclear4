<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Comercial (corte 3): solo Comercial lee y escribe estas tablas (AR-01).
 * Sin tabla de precios oficiales (son filas de registros_precio, ADR-0015) ni de
 * promociones proyectadas (Comparación las lee por el puerto PromocionesVigentes, ADR-0023).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles_comerciales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('establecimiento_id')->index();
            $table->foreign('establecimiento_id')->references('id')->on('establecimientos');
            $table->string('nombre_contacto', 120);          // privado (EC-07)
            $table->string('telefono_contacto', 30)->nullable(); // privado (EC-07)
            $table->string('estado', 12)->default('PENDIENTE'); // PENDIENTE | VERIFICADO | REVOCADO
            $table->char('credencial_hash', 64)->nullable()->unique(); // SHA-256 (ADR-0022)
            $table->timestampTz('verificado_en')->nullable();
            $table->timestampsTz();
        });

        Schema::create('promociones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('perfil_comercial_id')->index();
            $table->foreign('perfil_comercial_id')->references('id')->on('perfiles_comerciales');
            $table->uuid('producto_id')->index();
            $table->string('tipo', 30);                  // DESCUENTO_PORCENTUAL | PRECIO_ESPECIAL | PAGUE_X_LLEVE_Y
            $table->decimal('valor', 12, 2)->nullable(); // porcentaje o precio especial
            $table->smallInteger('pague')->nullable();
            $table->smallInteger('lleve')->nullable();
            $table->timestampTz('inicio');
            $table->timestampTz('fin');
            $table->timestampTz('retirada_en')->nullable();
            $table->timestampTz('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promociones');
        Schema::dropIfExists('perfiles_comerciales');
    }
};
