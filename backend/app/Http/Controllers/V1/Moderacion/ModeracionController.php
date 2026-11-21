<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Moderacion;

use App\Domains\Catalogo\Application\ConsultarCatalogo;
use App\Domains\Comercial\Application\GestionarPerfiles;
use App\Domains\Comercial\Domain\ValueObjects\EstadoPerfil;
use App\Domains\Confianza\Domain\Repositories\EvaluacionesRepositorio;
use App\Domains\Establecimientos\Application\ConsultarDirectorio;
use App\Domains\Moderacion\Application\ConsultarCasos;
use App\Domains\Moderacion\Application\ResolverCaso;
use App\Domains\Moderacion\Domain\Exceptions\CasoNoEncontradoException;
use App\Domains\Moderacion\Domain\Exceptions\DecisionNoAplicableException;
use App\Domains\Moderacion\Domain\ValueObjects\AccionModeracion;
use App\Domains\Moderacion\Domain\ValueObjects\CasoModeracion;
use App\Domains\Precios\Domain\Repositories\ObservacionesLectura;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Consola de moderación (RF-20, RF-31). Adaptador de entrada: compone la cola de Moderación con
 * la interfaz pública de Precios, Catálogo, Establecimientos y Confianza para que el moderador
 * decida con contexto. Nunca expone el seudónimo del reportante (EC-07).
 */
final class ModeracionController extends Controller
{
    /** Hasta tener cuentas (contexto Identidad) la credencial de moderación es compartida. */
    private const MODERADOR = 'moderacion';
    private const TAMANO_MAXIMO = 50;

    public function __construct(
        private readonly ConsultarCasos          $casos,
        private readonly ResolverCaso            $resolver,
        private readonly ObservacionesLectura    $observaciones,
        private readonly EvaluacionesRepositorio $evaluaciones,
        private readonly ConsultarCatalogo       $catalogo,
        private readonly ConsultarDirectorio     $directorio,
        private readonly GestionarPerfiles       $perfiles,
    ) {}

    /** GET /v1/moderacion/casos */
    public function casos(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'estado' => ['sometimes', Rule::in(['ABIERTO', 'RESUELTO'])],
            'pagina' => ['sometimes', 'integer', 'min:1'],
            'tamano' => ['sometimes', 'integer', 'min:1', 'max:' . self::TAMANO_MAXIMO],
        ]);
        [$pagina, $tamano] = [(int) ($datos['pagina'] ?? 1), (int) ($datos['tamano'] ?? 20)];
        $resultado = $this->casos->listar((string) ($datos['estado'] ?? 'ABIERTO'), $pagina, $tamano);

        return response()->json([
            'items'  => array_map(fn (CasoModeracion $c): array => $this->casoConContexto($c), $resultado['items']),
            'pagina' => ['pagina' => $pagina, 'tamano' => $tamano, 'total' => $resultado['total']],
        ])->header('Cache-Control', 'no-store');
    }

    /** POST /v1/moderacion/casos/{casoId}/decision */
    public function decidir(Request $request, string $casoId): JsonResponse
    {
        $datos = $request->validate([
            'accion'        => ['required', Rule::enum(AccionModeracion::class)],
            'justificacion' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        try {
            $caso = $this->resolver->resolver($casoId, AccionModeracion::from((string) $datos['accion']), (string) $datos['justificacion'], self::MODERADOR);
        } catch (CasoNoEncontradoException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        } catch (DecisionNoAplicableException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        }

        return response()->json($this->casoConContexto($caso));
    }

    /** GET /v1/moderacion/perfiles — sin nombre ni teléfono de contacto. */
    public function perfiles(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'estado' => ['sometimes', Rule::enum(EstadoPerfil::class)],
            'pagina' => ['sometimes', 'integer', 'min:1'],
            'tamano' => ['sometimes', 'integer', 'min:1', 'max:' . self::TAMANO_MAXIMO],
        ]);
        [$pagina, $tamano] = [(int) ($datos['pagina'] ?? 1), (int) ($datos['tamano'] ?? 20)];
        $resultado = $this->perfiles->listar(EstadoPerfil::from((string) ($datos['estado'] ?? 'PENDIENTE')), $pagina, $tamano);

        return response()->json([
            'items' => array_map(function (array $fila): array {
                $est = $this->directorio->porId($fila['perfil']->establecimientoId);

                return [
                    'perfilId'        => $fila['perfil']->id,
                    'estado'          => $fila['perfil']->estado->value,
                    'solicitadoEn'    => $fila['solicitadoEn'],
                    'establecimiento' => $est === null ? null : ['id' => $est->id, 'nombre' => $est->nombre, 'direccion' => $est->direccion],
                ];
            }, $resultado['items']),
            'pagina' => ['pagina' => $pagina, 'tamano' => $tamano, 'total' => $resultado['total']],
        ])->header('Cache-Control', 'no-store');
    }

    /** @return array<string, mixed> */
    private function casoConContexto(CasoModeracion $c): array
    {
        return [
            'id'            => $c->id,
            'origen'        => $c->origen,
            'estado'        => $c->estado,
            'detalle'       => $c->detalle,
            'abiertoEn'     => $c->abiertoEn,
            'observacionId' => $c->tieneObservacion() ? $c->observacionId : null,
            'observacion'   => $c->tieneObservacion() ? $this->observacion($c->observacionId) : null,
        ];
    }

    /** @return array<string, mixed>|null */
    private function observacion(string $id): ?array
    {
        $obs = $this->observaciones->detalle($id);
        if ($obs === null) {
            return null;
        }
        $producto = $this->catalogo->resumen($obs->productoId);
        $est      = $this->directorio->porId($obs->establecimientoId);

        return [
            'precio'          => $obs->monto,
            'observadoEn'     => $obs->observadoEn,
            'fuente'          => $obs->fuente,
            'producto'        => $producto['nombre'] ?? null,
            'presentacion'    => $this->catalogo->presentacion($obs->presentacionId)?->descripcion,
            'establecimiento' => $est?->nombre,
            'estadoConfianza' => $this->evaluaciones->obtener($id)['estado'] ?? null,
        ];
    }
}
