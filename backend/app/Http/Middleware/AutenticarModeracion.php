<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Identidad\Domain\ValueObjects\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * ADR-0025 (reemplaza el secreto compartido MODERACION_TOKEN de ADR-0022): moderar exige una
 * sesión de una cuenta con rol MODERADOR. Sin sesión, 401; con sesión de otro rol, 403.
 */
final class AutenticarModeracion
{
    public function __construct(private readonly AutenticarCuenta $autenticacion) {}

    public function handle(Request $request, Closure $next): Response
    {
        $cuenta = $this->autenticacion->cuentaDe($request);
        if ($cuenta->rol !== Rol::MODERADOR) {
            throw new AccessDeniedHttpException('Tu cuenta no tiene permisos de moderación.');
        }
        $request->attributes->set(AutenticarCuenta::ATRIBUTO, $cuenta);

        return $next($request);
    }
}
