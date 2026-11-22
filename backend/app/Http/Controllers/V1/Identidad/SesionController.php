<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Identidad;

use App\Domains\Identidad\Application\GestionarSesiones;
use App\Domains\Identidad\Domain\Exceptions\CredencialesInvalidasException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/** ADR-0025: inicio y cierre de sesión. Límite de 5 intentos por minuto por IP y correo (ruta). */
final class SesionController extends Controller
{
    public function __construct(private readonly GestionarSesiones $sesiones) {}

    /** POST /v1/sesiones */
    public function iniciar(Request $request): JsonResponse
    {
        $datos = $request->validate(['correo' => ['required', 'string', 'max:200'], 'contrasena' => ['required', 'string', 'max:200']]);

        try {
            $registro = $this->sesiones->iniciar((string) $datos['correo'], (string) $datos['contrasena']);
        } catch (CredencialesInvalidasException $e) {
            // Mismo mensaje si el correo no existe: no se revela qué cuentas hay.
            throw new UnauthorizedHttpException('Bearer', $e->getMessage(), $e);
        }

        return response()->json(CuentaController::conSesion($registro))->header('Cache-Control', 'no-store');
    }

    /** DELETE /v1/sesiones/actual */
    public function cerrar(Request $request): Response
    {
        $this->sesiones->cerrar((string) $request->bearerToken());

        return response()->noContent();
    }
}
