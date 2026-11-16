<?php

declare(strict_types=1);

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

Route::prefix('v1')->group(function () {
    // ── Catálogo ───────────────────────────────────────────────────────
    Route::get('productos', [ProductoController::class, 'index']);
    Route::get('productos/{productoId}', [ProductoController::class, 'show'])->whereUuid('productoId');

    // ── Establecimientos ───────────────────────────────────────────────
    Route::get('establecimientos', [EstablecimientoController::class, 'index']);

    // ── Precios ────────────────────────────────────────────────────────
    Route::post('reportes-precio', [ReportePrecioController::class, 'store']);
});
