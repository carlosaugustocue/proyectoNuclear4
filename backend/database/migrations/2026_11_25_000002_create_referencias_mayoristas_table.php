<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Ingesta (RF-25, ADR-0010): precio mayorista promedio mensual por producto y mercado (SIPSA-DANE).
 * Es una referencia aparte: no es una observación de góndola y la comparación no la lee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referencias_mayoristas', function (Blueprint $table) {
            $table->foreignUuid('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('mercado', 80);
            $table->date('mes');
            $table->decimal('precio', 15, 2);
            $table->string('unidad_base', 6);           // KG | L | UNIDAD
            $table->string('codigo_cpc', 10)->nullable();
            $table->string('fuente', 20)->default('SIPSA-DANE');
            $table->timestampTz('actualizado_en');
            $table->primary(['producto_id', 'mercado', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referencias_mayoristas');
    }
};
