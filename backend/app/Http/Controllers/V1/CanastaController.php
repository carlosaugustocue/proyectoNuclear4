<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1;

use App\Domains\Canasta\Application\CompararCanasta;
use App\Domains\Canasta\Domain\Exceptions\CanastaInvalidaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CompararCanastaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

final class CanastaController extends Controller
{
    public function __construct(private readonly CompararCanasta $canasta) {}

    /**
     * POST /v1/canastas/comparacion — RF-27 a RF-30. Consulta segura e idempotente: la canasta
     * no se persiste ni se registra (ADR-0018); POST porque la lista no cabe en la URL.
     */
    public function comparar(CompararCanastaRequest $request): JsonResponse
    {
        try {
            $resultado = $this->canasta->comparar($request->items(), $request->orden(), $request->municipio());
        } catch (CanastaInvalidaException $e) {
            throw ValidationException::withMessages(['items' => $e->errores]);
        }

        return response()->json($resultado, 200, ['Cache-Control' => 'no-store'], JSON_UNESCAPED_UNICODE);
    }
}
