<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Comercial;

use App\Domains\Comercial\Application\PublicarComoComercio;
use App\Domains\Comercial\Domain\Entities\PerfilComercial;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AutenticarComercio;
use App\Http\Requests\V1\Comercial\CorreccionRequest;
use App\Http\Requests\V1\Comercial\PrecioOficialRequest;
use App\Http\Requests\V1\Comercial\PromocionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** RF-32 a RF-34: publicaciones de un comercio autenticado (AutenticarComercio). */
final class PublicacionComercialController extends Controller
{
    use TraduceErroresComerciales;

    public function __construct(private readonly PublicarComoComercio $publicar) {}

    /** POST /v1/comercial/precios-oficiales — 202, igual que un reporte (ADR-0015). */
    public function precioOficial(PrecioOficialRequest $request): JsonResponse
    {
        $resultado = self::traducir(fn () => $this->publicar->precioOficial(
            self::perfil($request),
            $request->string('presentacionId')->toString(),
            $request->string('establecimientoId')->toString(),
            $request->string('precio')->toString(),
            $request->claveIdempotencia(),
        ));

        return response()->json(
            ['observacionId' => $resultado->observacionId, 'estado' => 'PENDIENTE_EVALUACION'],
            $resultado->esReintento ? 200 : 202,
        );
    }

    /** POST /v1/comercial/promociones */
    public function promocion(PromocionRequest $request): JsonResponse
    {
        $resultado = self::traducir(fn (): array => $this->publicar->promocion(
            self::perfil($request),
            $request->string('establecimientoId')->toString(),
            $request->string('productoId')->toString(),
            $request->string('tipo')->toString(),
            $request->valor(),
            $request->entero('pague'),
            $request->entero('lleve'),
            $request->fecha('inicio'),
            $request->fecha('fin'),
        ));

        return response()->json($resultado, 201);
    }

    /** DELETE /v1/comercial/promociones/{promocionId} */
    public function retirarPromocion(Request $request, string $promocionId): Response
    {
        $retirada = self::traducir(fn (): bool => $this->publicar->retirarPromocion(self::perfil($request), $promocionId));
        if (! $retirada) {
            throw new NotFoundHttpException('La promoción no existe o no pertenece a este comercio.');
        }

        return response()->noContent();
    }

    /** POST /v1/comercial/correcciones */
    public function correccion(CorreccionRequest $request): JsonResponse
    {
        $referencia = self::traducir(fn (): string => $this->publicar->correccion(
            self::perfil($request),
            $request->string('establecimientoId')->toString(),
            $request->string('detalle')->toString(),
        ));

        return response()->json(['referencia' => $referencia, 'estado' => 'EN_MODERACION'], 202);
    }

    private static function perfil(Request $request): PerfilComercial
    {
        $perfil = $request->attributes->get(AutenticarComercio::ATRIBUTO);
        assert($perfil instanceof PerfilComercial);

        return $perfil;
    }
}
