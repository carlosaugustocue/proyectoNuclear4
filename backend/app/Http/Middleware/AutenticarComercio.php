<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Comercial\Application\GestionarPerfiles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/** ADR-0022: resuelve el perfil comercial a partir de la credencial Bearer. El dominio recibe el perfil, no el token. */
final class AutenticarComercio
{
    public const ATRIBUTO = 'perfilComercial';

    public function __construct(private readonly GestionarPerfiles $perfiles) {}

    public function handle(Request $request, Closure $next): Response
    {
        $credencial = $request->bearerToken();
        $perfil = $credencial === null ? null : $this->perfiles->autenticar($credencial);

        if ($perfil === null) {
            throw new UnauthorizedHttpException('Bearer', 'Se requiere una credencial de comercio válida.');
        }

        $request->attributes->set(self::ATRIBUTO, $perfil);

        return $next($request);
    }
}
