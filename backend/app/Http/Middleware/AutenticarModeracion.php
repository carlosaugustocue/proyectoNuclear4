<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * ADR-0022: mientras no exista Identidad, moderar exige el secreto MODERACION_TOKEN del entorno.
 * Sin secreto configurado, nadie puede moderar (falla cerrada).
 */
final class AutenticarModeracion
{
    public function handle(Request $request, Closure $next): Response
    {
        $esperado = config('services.moderacion.token');
        $recibido = $request->bearerToken();

        if (! is_string($esperado) || $esperado === '' || $recibido === null || ! hash_equals($esperado, $recibido)) {
            throw new UnauthorizedHttpException('Bearer', 'Se requiere la credencial de moderación.');
        }

        return $next($request);
    }
}
