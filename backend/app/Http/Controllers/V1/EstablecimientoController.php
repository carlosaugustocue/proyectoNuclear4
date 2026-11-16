<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1;

use App\Domains\Establecimientos\Application\ConsultarDirectorio;
use App\Domains\Establecimientos\Domain\ValueObjects\EstablecimientoPublico;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\BuscarEstablecimientosRequest;
use Illuminate\Http\JsonResponse;

final class EstablecimientoController extends Controller
{
    public function __construct(private readonly ConsultarDirectorio $directorio) {}

    /** GET /v1/establecimientos — RF-05, RF-06 */
    public function index(BuscarEstablecimientosRequest $request): JsonResponse
    {
        $resultado = $this->directorio->buscar(
            $request->texto(), $request->municipio(), $request->cerca(), $request->radioKm(),
            $request->pagina(), $request->tamano(),
        );

        return response()->json([
            'items'  => array_map(static fn (EstablecimientoPublico $e): array => $e->toArray(), $resultado['items']),
            'pagina' => ['pagina' => $request->pagina(), 'tamano' => $request->tamano(), 'total' => $resultado['total']],
        ]);
    }
}
