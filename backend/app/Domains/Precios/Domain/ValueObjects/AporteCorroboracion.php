<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\ValueObjects;

use DateTimeImmutable;

final class AporteCorroboracion
{
    public function __construct(
        public readonly string            $userId,
        public readonly string            $monto,
        public readonly DateTimeImmutable $registradoEn,
    ) {}
}
