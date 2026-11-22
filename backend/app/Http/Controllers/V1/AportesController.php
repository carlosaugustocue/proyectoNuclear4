<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1;

use App\Domains\Catalogo\Application\ConsultarCatalogo;
use App\Domains\Confianza\Application\ConfirmarPrecio;
use App\Domains\Confianza\Application\ConsultarReputacion;
use App\Domains\Confianza\Domain\Exceptions\ConfirmacionNoAplicableException;
use App\Domains\Confianza\Domain\Exceptions\ObservacionNoEncontradaException;
use App\Domains\Confianza\Domain\Repositories\EvaluacionesRepositorio;
use App\Domains\Establecimientos\Application\ConsultarDirectorio;
use App\Domains\Moderacion\Application\MarcarObservacion;
use App\Domains\Moderacion\Domain\Exceptions\MarcaDuplicadaException;
use App\Domains\Moderacion\Domain\Exceptions\ObservacionNoEncontradaException as MarcaSinObservacion;
use App\Domains\Moderacion\Domain\ValueObjects\MotivoMarca;
use App\Domains\Precios\Domain\Repositories\ObservacionesLectura;
use App\Domains\Precios\Domain\ValueObjects\DetalleObservacion;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AutenticarCuenta;
use App\Http\Problemas\SolicitudInvalidaException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Aportes con cuenta (ADR-0026): RF-10 confirmar «sigue igual», RF-19 marcar como dudoso y RF-12
 * mis aportes. Compone las interfaces públicas de Precios, Confianza, Moderación, Catálogo y Directorio.
 */
final class AportesController extends Controller
{
    private const TAMANO_MAXIMO = 50;
    private const PATRON_UUID   = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function __construct(
        private readonly ConfirmarPrecio         $confirmar,
        private readonly MarcarObservacion       $marcar,
        private readonly ObservacionesLectura    $observaciones,
        private readonly EvaluacionesRepositorio $evaluaciones,
        private readonly ConsultarReputacion     $reputacion,
        private readonly ConsultarCatalogo       $catalogo,
        private readonly ConsultarDirectorio     $directorio,
    ) {}

    /** POST /v1/observaciones/{observacionId}/confirmaciones */
    public function confirmar(Request $request, string $observacionId): JsonResponse
    {
        $clave = $request->header('Idempotency-Key');
        if (! is_string($clave) || preg_match(self::PATRON_UUID, $clave) !== 1) {
            throw new SolicitudInvalidaException('La cabecera Idempotency-Key es obligatoria y debe ser un UUID');
        }

        try {
            $r = $this->confirmar->confirmar($observacionId, AutenticarCuenta::de($request)->id, strtolower($clave));
        } catch (ObservacionNoEncontradaException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        } catch (ConfirmacionNoAplicableException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        }

        return response()->json(['observacionId' => $r->observacionId, 'estado' => 'PENDIENTE_EVALUACION'], $r->esReintento ? 200 : 202);
    }

    /** POST /v1/observaciones/{observacionId}/marcas */
    public function marcar(Request $request, string $observacionId): JsonResponse
    {
        $datos = $request->validate([
            'motivo'     => ['required', Rule::enum(MotivoMarca::class)],
            'comentario' => ['sometimes', 'nullable', 'string', 'max:280'],
        ]);

        try {
            $this->marcar->marcar($observacionId, AutenticarCuenta::de($request)->id,
                MotivoMarca::from((string) $datos['motivo']), isset($datos['comentario']) ? (string) $datos['comentario'] : null);
        } catch (MarcaSinObservacion $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        } catch (MarcaDuplicadaException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        }

        return response()->json(['estado' => 'EN_REVISION', 'mensaje' => 'Gracias. Un moderador revisará este precio.'], 201);
    }

    /** GET /v1/mis-aportes */
    public function misAportes(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'pagina' => ['sometimes', 'integer', 'min:1'],
            'tamano' => ['sometimes', 'integer', 'min:1', 'max:' . self::TAMANO_MAXIMO],
        ]);
        [$pagina, $tamano] = [(int) ($datos['pagina'] ?? 1), (int) ($datos['tamano'] ?? 20)];
        $cuentaId  = AutenticarCuenta::de($request)->id;
        $resultado = $this->observaciones->aportesDe($cuentaId, $pagina, $tamano);

        return response()->json([
            'items'      => array_map(fn (DetalleObservacion $o): array => $this->aporte($o), $resultado['items']),
            'pagina'     => ['pagina' => $pagina, 'tamano' => $tamano, 'total' => $resultado['total']],
            'reputacion' => $this->reputacion->resumen($cuentaId),
        ])->header('Cache-Control', 'no-store');
    }

    /** @return array<string, mixed> */
    private function aporte(DetalleObservacion $o): array
    {
        return [
            'observacionId'   => $o->id,
            'producto'        => $this->catalogo->resumen($o->productoId)['nombre'] ?? null,
            'presentacion'    => $this->catalogo->presentacion($o->presentacionId)?->descripcion,
            'establecimiento' => $this->directorio->porId($o->establecimientoId)?->nombre,
            'precio'          => $o->monto,
            'observadoEn'     => $o->observadoEn,
            'estadoConfianza' => $this->evaluaciones->obtener($o->id)['estado'] ?? 'PENDIENTE_EVALUACION',
        ];
    }
}
