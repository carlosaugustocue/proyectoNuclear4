<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Confianza (ADR-0007) y Moderación (EC-03).
 * Los reportes sospechosos se marcan y se envían a moderación; no se eliminan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_confianza', function (Blueprint $table) {
            $table->uuid('observacion_id')->primary();
            $table->string('reportado_por', 36)->index();  // seudónimo, solo uso interno (RN-07)
            $table->string('estado', 16);                   // CONFIABLE | NO_CORROBORADO | POR_VERIFICAR | NO_CONFIABLE
            $table->unsignedSmallInteger('confirmaciones')->default(0);
            $table->string('permiso', 32);                  // PermisoAporte
            $table->boolean('confirmada')->default(false);  // corroborada por otro reportante
            $table->timestampTz('evaluado_en');
        });

        Schema::create('casos_moderacion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('observacion_id')->index();
            $table->string('origen', 24);                   // ANOMALIA_AUTOMATICA | MARCA_USUARIO
            $table->string('estado', 12)->default('ABIERTO');
            $table->string('detalle', 200)->nullable();
            $table->timestampTz('creado_en');
            $table->unique(['observacion_id', 'origen']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casos_moderacion');
        Schema::dropIfExists('evaluaciones_confianza');
    }
};
