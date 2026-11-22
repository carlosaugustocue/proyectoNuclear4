<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad en toda respuesta (C-18), incluidos los errores en Problem Details.
 * La API solo devuelve JSON: su CSP no permite cargar nada ni ser embebida en un iframe.
 */
final class CabecerasSeguridad
{
    private const CABECERAS = [
        'X-Content-Type-Options'       => 'nosniff',
        'X-Frame-Options'              => 'DENY',
        'Referrer-Policy'              => 'no-referrer',
        'Content-Security-Policy'      => "default-src 'none'; frame-ancestors 'none'",
        'Permissions-Policy'           => 'camera=(), microphone=(), geolocation=(), payment=()',
        // La PWA vive en otro origen (otro subdominio de Railway): la API debe poder leerse desde allí.
        'Cross-Origin-Resource-Policy' => 'cross-origin',
    ];

    private const HSTS = 'max-age=31536000; includeSubDomains';

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $respuesta */
        $respuesta = $next($request);

        foreach (self::CABECERAS as $nombre => $valor) {
            $respuesta->headers->set($nombre, $valor);
        }
        if ($request->isSecure()) {
            $respuesta->headers->set('Strict-Transport-Security', self::HSTS);
        }
        $respuesta->headers->remove('X-Powered-By');

        return $respuesta;
    }
}
