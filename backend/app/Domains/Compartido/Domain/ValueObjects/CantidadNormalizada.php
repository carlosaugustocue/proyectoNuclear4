<?php

declare(strict_types=1);

namespace App\Domains\Compartido\Domain\ValueObjects;

final class CantidadNormalizada
{
    public function __construct(
        public readonly float  $cantidad,
        public readonly string $unidadBase,
    ) {}
}
