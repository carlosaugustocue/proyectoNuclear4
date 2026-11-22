<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF-19 (ADR-0026): un usuario con cuenta marca un precio como dudoso. Una marca por cuenta y
 * observación. Al suprimir la cuenta (RF-23) se borran sus marcas, porque el comentario es texto libre.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcas_observacion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('observacion_id')->index();
            $table->uuid('cuenta_id');
            $table->foreign('cuenta_id')->references('id')->on('cuentas')->cascadeOnDelete();
            $table->string('motivo', 30);
            $table->string('comentario', 280)->nullable();
            $table->timestampTz('creado_en');
            $table->unique(['observacion_id', 'cuenta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marcas_observacion');
    }
};
