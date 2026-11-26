<?php

declare(strict_types=1);

namespace App\Http\Problemas;

use App\Domains\Precios\Domain\Exceptions\LimiteAportesExcedidoException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

/**
 * Traduce excepciones a respuestas RFC 9457 (application/problem+json) para /v1/*.
 * Nunca expone trazas ni mensajes internos en errores 5xx.
 */
final class ProblemDetails
{
    private const TIPO_BASE = 'https://proyecto-nuclear.unihumboldt.edu.co/problemas/';

    private const TITULOS_HTTP = [
        401 => 'No autenticado',
        403 => 'Acceso denegado',
        404 => 'Recurso no encontrado',
        405 => 'Método no permitido',
        409 => 'Conflicto',
        422 => 'La solicitud viola una regla del dominio',
        429 => 'Demasiadas solicitudes',
    ];

    /** Mensajes en español para las excepciones que lanza el propio framework (con texto en inglés). */
    private const DETALLES_FRAMEWORK = [
        404 => 'La ruta solicitada no existe.',
        405 => 'Esta operación no está permitida en este recurso.',
        429 => 'Demasiadas solicitudes. Espera un momento e inténtalo de nuevo.',
    ];

    public static function desde(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof SolicitudInvalidaException => self::respuesta(400, 'solicitud-invalida',
                'Solicitud inválida', $e->getMessage(), ['errors' => $e->errores]),
            $e instanceof ValidationException => self::respuesta(422, 'validacion',
                'Los datos enviados no son válidos', $e->getMessage(), ['errors' => $e->errors()]),
            $e instanceof LimiteAportesExcedidoException => self::respuesta(429, 'limite-aportes',
                'Límite de aportes excedido', $e->getMessage(), [],
                ['Retry-After' => (string) $e->reintentarEnSegundos]),
            $e instanceof InvalidArgumentException => self::respuesta(422, 'regla-dominio',
                'La solicitud viola una regla del dominio', $e->getMessage()),
            $e instanceof HttpExceptionInterface => self::respuesta($e->getStatusCode(),
                $e->getStatusCode() === 404 ? 'no-encontrado' : 'http',
                self::TITULOS_HTTP[$e->getStatusCode()] ?? 'Error HTTP',
                self::detalleHttp($e), [],
                $e->getHeaders()),
            default => self::respuesta(500, 'error-interno', 'Error interno',
                'Ocurrió un error inesperado. Intente de nuevo más tarde.'),
        };
    }

    /**
     * Los mensajes de nuestro código ya están en español; los del framework (ruta inexistente,
     * método no permitido, límite de solicitudes) vienen en inglés y se reemplazan.
     */
    private static function detalleHttp(HttpExceptionInterface $e): string
    {
        $mensaje = $e->getMessage();
        $esDelFramework = $mensaje === ''
            || $e instanceof MethodNotAllowedHttpException
            || $e instanceof ThrottleRequestsException
            || str_starts_with($mensaje, 'The route ');

        if (! $esDelFramework) {
            return $mensaje;
        }

        return self::DETALLES_FRAMEWORK[$e->getStatusCode()] ?? 'No se pudo completar la solicitud.';
    }

    /**
     * @param array<string, mixed>  $extensiones
     * @param array<string, string> $cabeceras
     */
    private static function respuesta(
        int $estado,
        string $tipo,
        string $titulo,
        string $detalle,
        array $extensiones = [],
        array $cabeceras = [],
    ): JsonResponse {
        return new JsonResponse(
            array_merge([
                'type'   => self::TIPO_BASE . $tipo,
                'title'  => $titulo,
                'status' => $estado,
                'detail' => $detalle,
            ], $extensiones),
            $estado,
            array_merge(['Content-Type' => 'application/problem+json'], $cabeceras),
            JSON_UNESCAPED_UNICODE,
        );
    }
}
