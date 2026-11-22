<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto Identidad (RF-22 a RF-24, ADR-0025).
 * - cuentas: el consentimiento (Ley 1581) es obligatorio: fecha y versión de la política aceptada.
 * - sesiones: solo se guarda el SHA-256 del token; el token en claro nunca toca la base.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('correo', 120)->unique();          // normalizado a minúsculas
            $table->string('contrasena_hash', 255);
            $table->string('rol', 16)->default('CIUDADANO');
            $table->string('estado', 16)->default('ACTIVA');
            $table->timestampTz('consentimiento_en');
            $table->string('version_politica', 20);
            $table->timestampTz('creado_en');
            $table->timestampTz('actualizado_en');
        });

        Schema::create('sesiones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cuenta_id')->index();
            $table->foreign('cuenta_id')->references('id')->on('cuentas')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestampTz('creado_en');
            $table->timestampTz('expira_en')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones');
        Schema::dropIfExists('cuentas');
    }
};
