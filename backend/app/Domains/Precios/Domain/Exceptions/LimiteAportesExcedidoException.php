<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Exceptions;

use RuntimeException;

/** RN-08: más de 30 reportes en la última hora para el mismo reportante. */
final class LimiteAportesExcedidoException extends RuntimeException
{
    public function __construct(public readonly int $reintentarEnSegundos = 3600)
    {
        parent::__construct('Se superó el máximo de 30 reportes por hora. Intente más tarde.');
    }
}
