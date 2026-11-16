<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1;

use App\Domains\Catalogo\Application\ConsultarCatalogo;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\BuscarProductosRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProductoController extends Controller
{
    public function __construct(private readonly ConsultarCatalogo $catalogo) {}

    /** GET /v1/productos — RF-01 */
    public function index(BuscarProductosRequest $request): JsonResponse
    {
        $resultado = $this->catalogo->buscar(
            $request->texto(), $request->ean(), $request->categoria(), $request->pagina(), $request->tamano(),
        );

        return response()->json([
            'items'  => $resultado['items'],
            'pagina' => ['pagina' => $request->pagina(), 'tamano' => $request->tamano(), 'total' => $resultado['total']],
        ]);
    }

    /** GET /v1/productos/{productoId} — RF-02 */
    public function show(string $productoId): JsonResponse
    {
        $producto = $this->catalogo->detalle($productoId);
        if ($producto === null) {
            throw new NotFoundHttpException('El producto no existe en el catálogo');
        }

        return response()->json($producto);
    }
}
