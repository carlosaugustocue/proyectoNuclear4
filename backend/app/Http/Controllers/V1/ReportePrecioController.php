<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1;

use App\Domains\Precios\Application\Commands\RegistrarReportePrecioCommand;
use App\Domains\Precios\Application\Handlers\RegistrarReportePrecioHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\RegistrarReportePrecioRequest;
use App\Http\Support\SeudonimoReportante;
use Illuminate\Http\JsonResponse;

final class ReportePrecioController extends Controller
{
    public function __construct(
        private readonly RegistrarReportePrecioHandler     $handler,
        private readonly \App\Http\Middleware\AutenticarCuenta $autenticacion,
    ) {}

    /**
     * POST /v1/reportes-precio — RF-08, RF-09.
     * 202 si es nuevo; 200 si es un reintento con una Idempotency-Key ya procesada (EC-09).
     */
    public function store(RegistrarReportePrecioRequest $request): JsonResponse
    {
        $resultado = $this->handler->handle(new RegistrarReportePrecioCommand(
            presentacionId:    $request->string('presentacionId')->toString(),
            establecimientoId: $request->string('establecimientoId')->toString(),
            precio:            $request->string('precio')->toString(),
            observadoEn:       $request->observadoEn(),
            reportadoPor:      $this->reportante($request),
            claveIdempotencia: $request->claveIdempotencia(),
        ));

        return response()->json(
            ['observacionId' => $resultado->observacionId, 'estado' => 'PENDIENTE_EVALUACION'],
            $resultado->esReintento ? 200 : 202,
        );
    }

    /**
     * Con sesión, el reporte queda a nombre de la cuenta (RN-07 por cuenta, ADR-0025); un token
     * inválido da 401 en lugar de caer en anónimo. Sin sesión, seudónimo HMAC de la IP (RF-22 opcional).
     */
    private function reportante(\Illuminate\Http\Request $request): string
    {
        return $request->bearerToken() !== null
            ? $this->autenticacion->cuentaDe($request)->id
            : SeudonimoReportante::desde($request->ip(), (string) config('app.key'));
    }
}
