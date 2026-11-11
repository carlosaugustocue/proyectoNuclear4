<?php

declare(strict_types=1);

use App\Http\Controllers\V1\PrecioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — versión 1
|--------------------------------------------------------------------------
| Prefijo /v1 configurado en bootstrap/app.php
*/

Route::prefix('v1')->group(function () {
    // ── Precios ────────────────────────────────────────────────────────
    Route::post('precios', [PrecioController::class, 'store']);
});
