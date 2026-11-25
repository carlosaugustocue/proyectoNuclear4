<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1;

use App\Domains\Ingesta\Application\ConsultarReferenciaMayorista;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ReferenciaMayoristaController extends Controller
{
    public function __construct(private readonly ConsultarReferenciaMayorista $consulta) {}

    /** GET /v1/productos/{productoId}/referencia-mayorista — RF-25, ADR-0010 */
    public function show(string $productoId): JsonResponse
    {
        $referencia = $this->consulta->consultar($productoId);
        if ($referencia === null) {
            throw new NotFoundHttpException('El producto no existe en el catálogo');
        }

        return response()->json($referencia);
    }
}
