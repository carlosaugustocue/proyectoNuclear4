<?php

declare(strict_types=1);

use App\Http\Controllers\V1\ComparacionController;
use App\Http\Controllers\V1\EstablecimientoController;
use App\Http\Controllers\V1\ReportePrecioController;
use App\Http\Controllers\V1\ProductoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — contrato en docs/03-arquitectura/api/openapi.yaml
|--------------------------------------------------------------------------
| Sin prefijo /api: las rutas coinciden con `servers: http://localhost:8080/v1`
| (ver apiPrefix en bootstrap/app.php).
*/

Route::prefix('v1')->middleware('throttle:api-por-ip')->group(function () {
    // ── Catálogo ───────────────────────────────────────────────────────
    Route::get('productos', [ProductoController::class, 'index']);
    Route::get('productos/{productoId}', [ProductoController::class, 'show'])->whereUuid('productoId');

    // ── Establecimientos ───────────────────────────────────────────────
    Route::get('establecimientos', [EstablecimientoController::class, 'index']);

    // ── Precios ────────────────────────────────────────────────────────
    Route::post('reportes-precio', [ReportePrecioController::class, 'store']);

    // ── Comparación (modelo de lectura) ────────────────────────────────
    Route::get('comparaciones', [ComparacionController::class, 'index']);
    Route::get('comparaciones/resumen', [ComparacionController::class, 'resumen']);

    // Canasta comparada (Anexo 1, A1; ADR-0017 a ADR-0019)
    // Módulo Comercial (corte 3) — RF-31 a RF-34, ADR-0022, ADR-0023
    Route::post('comercial/perfiles', [\App\Http\Controllers\V1\Comercial\PerfilComercialController::class, 'solicitar']);
    Route::middleware(\App\Http\Middleware\AutenticarModeracion::class)->prefix('moderacion/perfiles/{perfilId}')->group(function () {
        Route::post('verificacion', [\App\Http\Controllers\V1\Comercial\PerfilComercialController::class, 'verificar'])->whereUuid('perfilId');
        Route::post('revocacion', [\App\Http\Controllers\V1\Comercial\PerfilComercialController::class, 'revocar'])->whereUuid('perfilId');
    });
    // Consola de moderación (RF-20): cola de casos, decisiones con bitácora y perfiles pendientes
    Route::middleware(\App\Http\Middleware\AutenticarModeracion::class)->prefix('moderacion')->group(function () {
        Route::get('casos', [\App\Http\Controllers\V1\Moderacion\ModeracionController::class, 'casos']);
        Route::post('casos/{casoId}/decision', [\App\Http\Controllers\V1\Moderacion\ModeracionController::class, 'decidir'])->whereUuid('casoId');
        Route::get('perfiles', [\App\Http\Controllers\V1\Moderacion\ModeracionController::class, 'perfiles']);
    });
    Route::middleware(\App\Http\Middleware\AutenticarComercio::class)->prefix('comercial')->group(function () {
        Route::post('precios-oficiales', [\App\Http\Controllers\V1\Comercial\PublicacionComercialController::class, 'precioOficial']);
        Route::post('promociones', [\App\Http\Controllers\V1\Comercial\PublicacionComercialController::class, 'promocion']);
        Route::delete('promociones/{promocionId}', [\App\Http\Controllers\V1\Comercial\PublicacionComercialController::class, 'retirarPromocion'])->whereUuid('promocionId');
        Route::post('correcciones', [\App\Http\Controllers\V1\Comercial\PublicacionComercialController::class, 'correccion']);
    });

    Route::post('canastas/comparacion', [\App\Http\Controllers\V1\CanastaController::class, 'comparar']);
});
