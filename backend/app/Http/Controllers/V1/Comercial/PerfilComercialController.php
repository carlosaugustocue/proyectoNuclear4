<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Comercial;

use App\Domains\Comercial\Application\GestionarPerfiles;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Comercial\SolicitarPerfilRequest;
use Illuminate\Http\JsonResponse;

/** RF-31 y ADR-0022. Las respuestas nunca incluyen los datos de contacto (EC-07). */
final class PerfilComercialController extends Controller
{
    use TraduceErroresComerciales;

    public function __construct(private readonly GestionarPerfiles $perfiles) {}

    /** POST /v1/comercial/perfiles */
    public function solicitar(SolicitarPerfilRequest $request): JsonResponse
    {
        $perfil = $this->perfiles->solicitar(
            $request->string('establecimientoId')->toString(),
            $request->string('nombreContacto')->toString(),
            $request->filled('telefonoContacto') ? $request->string('telefonoContacto')->toString() : null,
        );

        return response()->json(['perfilId' => $perfil->id, 'estado' => $perfil->estado->value], 201);
    }

    /** POST /v1/moderacion/perfiles/{perfilId}/verificacion — la credencial se muestra una sola vez. */
    public function verificar(string $perfilId): JsonResponse
    {
        $resultado = self::traducir(fn (): array => $this->perfiles->verificar($perfilId));

        return response()
            ->json([
                'perfilId'   => $resultado['perfil']->id,
                'estado'     => $resultado['perfil']->estado->value,
                'credencial' => $resultado['credencial'],
            ])
            ->header('Cache-Control', 'no-store');
    }

    /** POST /v1/moderacion/perfiles/{perfilId}/revocacion */
    public function revocar(string $perfilId): JsonResponse
    {
        $perfil = self::traducir(fn () => $this->perfiles->revocar($perfilId));

        return response()->json(['perfilId' => $perfil->id, 'estado' => $perfil->estado->value]);
    }
}
