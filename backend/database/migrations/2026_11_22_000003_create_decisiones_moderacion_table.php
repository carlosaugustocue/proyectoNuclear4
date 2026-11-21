<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Bitácora de decisiones de moderación (RF-20, RNF-11): solo se insertan filas, nunca se
 * actualizan ni se borran. Hasta que exista el contexto Identidad, el moderador se registra
 * como "moderacion" (credencial compartida, ADR-0022).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decisiones_moderacion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('caso_id')->index();
            $table->foreign('caso_id')->references('id')->on('casos_moderacion');
            $table->string('accion', 24);
            $table->string('justificacion', 500);
            $table->string('moderador', 60);
            $table->timestampTz('decidido_en');
        });

        Schema::table('casos_moderacion', function (Blueprint $table) {
            $table->index(['estado', 'creado_en']);
        });
    }

    public function down(): void
    {
        Schema::table('casos_moderacion', function (Blueprint $table) {
            $table->dropIndex(['estado', 'creado_en']);
        });
        Schema::dropIfExists('decisiones_moderacion');
    }
};
