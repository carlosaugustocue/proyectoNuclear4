<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\ValueObjects;

use Brick\Money\Money;
use InvalidArgumentException;

final class Precio
{
    private function __construct(
        private readonly Money $money,
    ) {}

    public static function de(string $monto, string $moneda): self
    {
        $money = Money::of($monto, $moneda);

        if ($money->isNegativeOrZero()) {
            throw new InvalidArgumentException('El precio debe ser mayor que cero');
        }

        return new self($money);
    }

    public function monto(): string
    {
        return (string) $this->money->getAmount();
    }

    public function moneda(): string
    {
        return $this->money->getCurrency()->getCurrencyCode();
    }

    public function toMoney(): Money
    {
        return $this->money;
    }

    public function equals(self $other): bool
    {
        return $this->money->isEqualTo($other->money);
    }
}
