<?php

declare(strict_types=1);

namespace App\Http\Controllers\V1\Comercial;

use App\Domains\Comercial\Domain\Exceptions\PerfilNoEncontradoException;
use App\Domains\Comercial\Domain\Exceptions\PromocionInvalidaException;
use App\Domains\Comercial\Domain\Exceptions\PublicacionNoAutorizadaException;
use DomainException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/** Traduce las excepciones del dominio Comercial a respuestas Problem Details (RFC 9457). */
trait TraduceErroresComerciales
{
    /**
     * @template T
     * @param  callable(): T $accion
     * @return T
     */
    private static function traducir(callable $accion): mixed
    {
        try {
            return $accion();
        } catch (PublicacionNoAutorizadaException $e) {
            throw new AccessDeniedHttpException($e->getMessage(), $e);
        } catch (PromocionInvalidaException $e) {
            throw ValidationException::withMessages(['promocion' => $e->errores]);
        } catch (PerfilNoEncontradoException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        } catch (DomainException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }
    }
}
