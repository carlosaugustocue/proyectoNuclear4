<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1;

use App\Domains\Precios\Application\Commands\RegistrarPrecioCommand;
use App\Domains\Precios\Application\Handlers\RegistrarPrecioHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\RegistrarPrecioRequest;
use Illuminate\Http\JsonResponse;

final class PrecioController extends Controller
{
    public function __construct(
        private readonly RegistrarPrecioHandler $handler,
    ) {}

    /**
     * POST /v1/precios
     * Registra un nuevo precio observado en un establecimiento.
     */
    public function store(RegistrarPrecioRequest $request): JsonResponse
    {
        $id = $this->handler->handle(new RegistrarPrecioCommand(
            productoId:        $request->string('producto_id')->toString(),
            establecimientoId: $request->string('establecimiento_id')->toString(),
            monto:             $request->string('monto')->toString(),
            moneda:            $request->string('moneda', 'COP')->toString(),
            latitud:           (float) $request->input('latitud'),
            longitud:          (float) $request->input('longitud'),
            reportadoPor:      'anon-' . $request->ip(), // temporal hasta implementar Auth
        ));

        return response()->json(['id' => $id], 201);
    }
}
