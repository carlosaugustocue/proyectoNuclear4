<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\ValueObjects;

use DateTimeImmutable;

final class CuentaContribuidora
{
    public function __construct(
        public readonly string            $id,
        public readonly DateTimeImmutable $creadaEn,
        public readonly int               $aportesConfirmados,
        public readonly bool              $suspendida,
    ) {}
}
