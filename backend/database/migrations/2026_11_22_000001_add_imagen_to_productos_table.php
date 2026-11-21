<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Imagen opcional del producto (catálogo). Solo fotos con licencia libre y crédito visible
 * (p. ej., Open Food Facts, CC BY-SA). Nunca imágenes copiadas de catálogos de cadenas (R-06).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('imagen_url', 300)->nullable();
            $table->string('imagen_credito', 160)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['imagen_url', 'imagen_credito']);
        });
    }
};
