<?php

declare(strict_types=1);

namespace App\Domains\Precios\Application\Handlers;

final class ResultadoRegistro
{
    public function __construct(
        public readonly string $observacionId,
        public readonly bool   $esReintento,
    ) {}
}
