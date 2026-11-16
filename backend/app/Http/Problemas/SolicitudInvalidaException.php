<?php

declare(strict_types=1);

namespace App\Http\Problemas;

use RuntimeException;

/**
 * Parámetros de consulta o cabeceras inválidos → 400 Bad Request (contrato OpenAPI).
 * Los errores de cuerpo (JSON) siguen siendo 422 por ValidationException.
 */
final class SolicitudInvalidaException extends RuntimeException
{
    /** @param array<string, list<string>> $errores */
    public function __construct(string $detalle, public readonly array $errores = [])
    {
        parent::__construct($detalle);
    }
}
