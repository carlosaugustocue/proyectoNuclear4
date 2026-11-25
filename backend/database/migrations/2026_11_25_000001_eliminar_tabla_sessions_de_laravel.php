<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hallazgo de la auditoría D-06 (`calidad:auditar-datos`): la tabla `sessions` de la plantilla de Laravel
 * guardaba la IP y el user agent de quien visitara la raíz de la API (SESSION_DRIVER=database por defecto).
 * La API no usa sesiones de Laravel: las sesiones de las cuentas viven en `sesiones` (ADR-0025) y no guardan
 * rastros del dispositivo. Se elimina la tabla y el driver por defecto pasa a `array` (R-01, EC-07).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sessions');
    }

    public function down(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
};
