<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Identidad\Application\GestionarSesiones;
use App\Domains\Identidad\Domain\Entities\Cuenta;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * ADR-0025: resuelve la cuenta a partir del token de sesión (Bearer). El dominio recibe la
 * cuenta, nunca el token. Una cuenta suspendida o una sesión vencida o revocada da 401.
 */
final class AutenticarCuenta
{
    public const ATRIBUTO = 'cuenta';

    public function __construct(private readonly GestionarSesiones $sesiones) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::ATRIBUTO, $this->cuentaDe($request));

        return $next($request);
    }

    /** Cuenta autenticada o 401. Lo usan también los endpoints donde la sesión es opcional. */
    public function cuentaDe(Request $request): Cuenta
    {
        $token  = $request->bearerToken();
        $cuenta = $token === null ? null : $this->sesiones->autenticar($token);

        return $cuenta ?? throw new UnauthorizedHttpException('Bearer', 'Inicia sesión para continuar.');
    }

    public static function de(Request $request): Cuenta
    {
        $cuenta = $request->attributes->get(self::ATRIBUTO);
        assert($cuenta instanceof Cuenta);

        return $cuenta;
    }
}
