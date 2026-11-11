<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Entities;

use App\Domains\Precios\Domain\Events\PrecioRegistrado;
use App\Domains\Precios\Domain\ValueObjects\Coordenadas;
use App\Domains\Precios\Domain\ValueObjects\Precio;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class RegistroPrecio
{
    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        private readonly string          $id,
        private readonly string          $productoId,
        private readonly string          $establecimientoId,
        private readonly Precio          $precio,
        private readonly Coordenadas     $coordenadas,
        private readonly string          $reportadoPor,
        private readonly DateTimeImmutable $registradoEn,
    ) {}

    public static function crear(
        string      $productoId,
        string      $establecimientoId,
        Precio      $precio,
        Coordenadas $coordenadas,
        string      $reportadoPor,
    ): self {
        $id = Uuid::uuid7()->toString();

        $registro = new self(
            id:                 $id,
            productoId:         $productoId,
            establecimientoId:  $establecimientoId,
            precio:             $precio,
            coordenadas:        $coordenadas,
            reportadoPor:       $reportadoPor,
            registradoEn:       new DateTimeImmutable(),
        );

        $registro->domainEvents[] = new PrecioRegistrado(
            registroId:        $id,
            productoId:        $productoId,
            establecimientoId: $establecimientoId,
            monto:             $precio->monto(),
            moneda:            $precio->moneda(),
            latitud:           $coordenadas->latitud(),
            longitud:          $coordenadas->longitud(),
            reportadoPor:      $reportadoPor,
        );

        return $registro;
    }

    public function id(): string               { return $this->id; }
    public function productoId(): string       { return $this->productoId; }
    public function establecimientoId(): string { return $this->establecimientoId; }
    public function precio(): Precio           { return $this->precio; }
    public function coordenadas(): Coordenadas { return $this->coordenadas; }
    public function reportadoPor(): string     { return $this->reportadoPor; }
    public function registradoEn(): DateTimeImmutable { return $this->registradoEn; }

    /** @return list<object> */
    public function pullDomainEvents(): array
    {
        $eventos = $this->domainEvents;
        $this->domainEvents = [];
        return $eventos;
    }
}
