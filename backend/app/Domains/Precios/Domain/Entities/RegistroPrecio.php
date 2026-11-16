<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Entities;

use App\Domains\Compartido\Domain\ValueObjects\Precio;
use App\Domains\Precios\Domain\Events\PrecioRegistrado;
use App\Domains\Precios\Domain\ValueObjects\FuenteObservacion;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * Observación de precio de una presentación en un establecimiento (RF-08).
 *
 * Inmutable: una corrección es una observación nueva (las anteriores quedan como
 * historial). No contiene ubicación del dispositivo (privacidad por diseño, R-01).
 */
final class RegistroPrecio
{
    /** Tolerancia al desfase del reloj del dispositivo. */
    private const TOLERANCIA_RELOJ_SEGUNDOS = 300;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        private readonly string            $id,
        private readonly string            $presentacionId,
        private readonly string            $productoId,
        private readonly string            $establecimientoId,
        private readonly Precio            $precio,
        private readonly DateTimeImmutable $observadoEn,
        private readonly string            $reportadoPor,
        private readonly string            $claveIdempotencia,
        private readonly FuenteObservacion $fuente,
        private readonly DateTimeImmutable $registradoEn,
    ) {}

    public static function crear(
        string            $presentacionId,
        string            $productoId,
        string            $establecimientoId,
        Precio            $precio,
        DateTimeImmutable $observadoEn,
        string            $reportadoPor,
        string            $claveIdempotencia,
        FuenteObservacion $fuente = FuenteObservacion::REPORTANTE,
    ): self {
        $ahora = new DateTimeImmutable();
        if ($observadoEn->getTimestamp() > $ahora->getTimestamp() + self::TOLERANCIA_RELOJ_SEGUNDOS) {
            throw new InvalidArgumentException('La fecha de observación no puede estar en el futuro');
        }

        $registro = new self(
            id:                Uuid::uuid7()->toString(),
            presentacionId:    $presentacionId,
            productoId:        $productoId,
            establecimientoId: $establecimientoId,
            precio:            $precio,
            observadoEn:       $observadoEn,
            reportadoPor:      $reportadoPor,
            claveIdempotencia: $claveIdempotencia,
            fuente:            $fuente,
            registradoEn:      $ahora,
        );

        $registro->domainEvents[] = new PrecioRegistrado(
            registroId:        $registro->id,
            productoId:        $productoId,
            presentacionId:    $presentacionId,
            establecimientoId: $establecimientoId,
            monto:             $precio->monto(),
            moneda:            $precio->moneda(),
            observadoEn:       $observadoEn->format(DATE_ATOM),
            reportadoPor:      $reportadoPor,
            fuente:            $fuente->value,
        );

        return $registro;
    }

    public function id(): string                       { return $this->id; }
    public function presentacionId(): string           { return $this->presentacionId; }
    public function productoId(): string               { return $this->productoId; }
    public function establecimientoId(): string        { return $this->establecimientoId; }
    public function precio(): Precio                   { return $this->precio; }
    public function observadoEn(): DateTimeImmutable   { return $this->observadoEn; }
    public function reportadoPor(): string             { return $this->reportadoPor; }
    public function claveIdempotencia(): string        { return $this->claveIdempotencia; }
    public function fuente(): FuenteObservacion        { return $this->fuente; }
    public function registradoEn(): DateTimeImmutable  { return $this->registradoEn; }

    /** @return list<object> */
    public function pullDomainEvents(): array
    {
        $eventos = $this->domainEvents;
        $this->domainEvents = [];

        return $eventos;
    }
}
