<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Domain\ValueObjects;

use DateTimeImmutable;

final class AporteCorroboracion
{
    public function __construct(
        public readonly string            $userId,
        public readonly string            $monto,
        public readonly DateTimeImmutable $registradoEn,
    ) {}
}
