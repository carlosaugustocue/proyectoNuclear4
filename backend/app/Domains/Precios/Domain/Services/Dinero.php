<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Services;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

final class Dinero
{
    private function __construct(
        private readonly Money $money,
    ) {}

    public static function de(string $monto, string $moneda = 'COP'): self
    {
        return new self(Money::of($monto, $moneda));
    }

    /** Precio por kilogramo: precio / cantidadEnKg */
    public static function precioPorKg(self $precio, string $cantidadKg): self
    {
        $moneda    = $precio->money->getCurrency();
        $cantidad  = BigDecimal::of($cantidadKg);
        $resultado = $precio->money->getAmount()->dividedBy(
            $cantidad,
            scale: 2,
            roundingMode: RoundingMode::HALF_UP
        );
        return new self(Money::of($resultado, $moneda));
    }

    public static function dividir(self $precio, string $divisor): self
    {
        $moneda    = $precio->money->getCurrency();
        $resultado = $precio->money->getAmount()->dividedBy(
            BigDecimal::of($divisor),
            scale: 2,
            roundingMode: RoundingMode::HALF_UP
        );
        return new self(Money::of($resultado, $moneda));
    }

    /**
     * @param non-empty-list<self> $items
     */
    public static function sumar(array $items): self
    {
        $acum   = BigDecimal::zero();
        $moneda = $items[0]->money->getCurrency();
        foreach ($items as $item) {
            $acum = $acum->plus($item->money->getAmount());
        }
        return new self(Money::of($acum, $moneda));
    }

    public function formatoDisplay(): string
    {
        // Separar parte entera y decimal via string para evitar RoundingNecessaryException
        $str   = (string) $this->money->getAmount();
        $parts = explode('.', $str . '.00');
        $enteroVal = (int) $parts[0];
        // Tomar los 2 primeros dígitos decimales
        $decimal   = (int) substr(str_pad($parts[1], 2, '0'), 0, 2);

        $enteroFormateado = number_format($enteroVal, 0, ',', '.');
        return sprintf('$%s,%02d', $enteroFormateado, $decimal);
    }

    /** Indica si la división fue exacta (sin redondeo) */
    public function esExacto(): bool
    {
        $str = (string) $this->money->getAmount();
        if (! str_contains($str, '.')) {
            return true;
        }
        return rtrim(substr($str, strpos($str, '.') + 1), '0') === '';
    }
}
