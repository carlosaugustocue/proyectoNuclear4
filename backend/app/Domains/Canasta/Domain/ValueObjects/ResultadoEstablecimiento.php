<?php

declare(strict_types=1);

namespace App\Domains\Canasta\Domain\ValueObjects;

/** Total de la canasta en un establecimiento: solo suma lo disponible; los faltantes se listan aparte (ADR-0019). */
final class ResultadoEstablecimiento
{
    /**
     * @param list<LineaCanasta> $lineas
     * @param list<string>       $faltantes productoIds sin precio vigente en el establecimiento
     */
    public function __construct(
        public readonly string $establecimientoId,
        public readonly string $nombre,
        public readonly string $total,
        public readonly array $lineas,
        public readonly array $faltantes,
        public readonly int $totalProductos,
    ) {}

    public function disponibles(): int
    {
        return count($this->lineas);
    }

    public function completa(): bool
    {
        return $this->faltantes === [];
    }
}
