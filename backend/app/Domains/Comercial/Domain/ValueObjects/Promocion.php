<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Domain\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DateTimeImmutable;

/** Condiciones de una promoción ya validadas por RN-14. Solo se construye desde ValidadorPromocion. */
final class Promocion
{
    public function __construct(
        public readonly TipoPromocion     $tipo,
        public readonly ?string           $valor,
        public readonly ?int              $pague,
        public readonly ?int              $lleve,
        public readonly DateTimeImmutable $inicio,
        public readonly DateTimeImmutable $fin,
    ) {}

    /** Texto que ve el consumidor en el bloque de contenido promocional. */
    public function descripcion(): string
    {
        return match ($this->tipo) {
            TipoPromocion::DESCUENTO_PORCENTUAL => sprintf('%s %% de descuento', BigDecimal::of((string) $this->valor)->stripTrailingZeros()),
            TipoPromocion::PRECIO_ESPECIAL      => 'Precio especial $' . number_format(
                BigDecimal::of((string) $this->valor)->toScale(0, RoundingMode::HALF_UP)->toInt(), 0, ',', '.'
            ),
            TipoPromocion::PAGUE_X_LLEVE_Y      => sprintf('Pague %d, lleve %d', (int) $this->pague, (int) $this->lleve),
        };
    }
}
