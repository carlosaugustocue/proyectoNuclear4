<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\ValueObjects;

use InvalidArgumentException;

final class Coordenadas
{
    private function __construct(
        private readonly float $latitud,
        private readonly float $longitud,
    ) {}

    public static function de(float $latitud, float $longitud): self
    {
        if ($latitud < -90.0 || $latitud > 90.0) {
            throw new InvalidArgumentException('Latitud inválida: debe estar entre -90 y 90');
        }

        if ($longitud < -180.0 || $longitud > 180.0) {
            throw new InvalidArgumentException('Longitud inválida: debe estar entre -180 y 180');
        }

        return new self($latitud, $longitud);
    }

    public function latitud(): float
    {
        return $this->latitud;
    }

    public function longitud(): float
    {
        return $this->longitud;
    }

    public function equals(self $other): bool
    {
        return $this->latitud === $other->latitud
            && $this->longitud === $other->longitud;
    }

    /** Formato WKT para PostGIS: POINT(longitud latitud) */
    public function toWkt(): string
    {
        return "POINT({$this->longitud} {$this->latitud})";
    }
}
