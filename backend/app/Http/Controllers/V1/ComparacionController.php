<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1;

use App\Domains\Comparacion\Application\ConsultarComparacion;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\CompararPreciosRequest;
use App\Http\Requests\V1\ResumenComparacionRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ComparacionController extends Controller
{
    /** El modelo de lectura se puede cachear brevemente (EC-04); la actualidad se acota a ≤ 5 min (EC-02). */
    private const CACHE = 'public, max-age=30';

    public function __construct(private readonly ConsultarComparacion $comparacion) {}

    /** GET /v1/comparaciones — RF-13 a RF-17 */
    public function index(CompararPreciosRequest $request): JsonResponse
    {
        $resultado = $this->comparacion->comparar(
            $request->productoId(),
            $request->presentacionId(),
            $request->municipio(),
            $request->cerca(),
            $request->radioKm(),
            $request->orden(),
            $request->incluirNoVigentes(),
        );

        if ($resultado === null) {
            throw new NotFoundHttpException('El producto no existe en el catálogo');
        }

        return response()->json($resultado, 200, ['Cache-Control' => self::CACHE], JSON_UNESCAPED_UNICODE);
    }

    /** GET /v1/comparaciones/resumen — menor precio vigente por producto (tarjetas de la PWA) */
    public function resumen(ResumenComparacionRequest $request): JsonResponse
    {
        return response()->json(
            ['items' => $this->comparacion->resumen($request->productoIds())],
            200,
            ['Cache-Control' => self::CACHE],
        );
    }
}
