<?php

use App\Http\Problemas\ProblemDetails;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Detrás del proxy de Railway: se confía SOLO en el salto inmediato (REMOTE_ADDR). Así
        // request->ip() es la IP real del cliente (la última que agregó el proxy en X-Forwarded-For)
        // y un cliente no puede suplantar a otro anteponiendo IPs falsas (C-18; RN-05, RN-07, RN-08).
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT);
        $middleware->append(\App\Http\Middleware\CabecerasSeguridad::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Errores de la API en formato RFC 9457 (Problem Details) — ADR-0004
        $exceptions->render(function (Throwable $e, Request $request) {
            return $request->is('v1/*') ? ProblemDetails::desde($e) : null;
        });
    })->create();
