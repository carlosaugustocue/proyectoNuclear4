<?php

declare(strict_types=1);

namespace App\Domains\Establecimientos\Domain\ValueObjects;

/** Vista pública de un establecimiento (interfaz pública del contexto, AR-01). */
final class EstablecimientoPublico
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $nombre,
        public readonly string  $tipo,
        public readonly ?string $direccion,
        public readonly ?string $barrio,
        public readonly string  $municipio,
        public readonly float   $lat,
        public readonly float   $lon,
        public readonly string  $estadoDirectorio,
        public readonly ?float  $distanciaKm = null,
    ) {}

    /** @return array<string, mixed> Esquema `Establecimiento` del contrato */
    public function toArray(): array
    {
        $datos = [
            'id'               => $this->id,
            'nombre'           => $this->nombre,
            'tipo'             => $this->tipo,
            'direccion'        => $this->direccion,
            'barrio'           => $this->barrio,
            'municipio'        => $this->municipio,
            'ubicacion'        => ['lat' => $this->lat, 'lon' => $this->lon],
            'estadoDirectorio' => $this->estadoDirectorio,
        ];

        if ($this->distanciaKm !== null) {
            $datos['distanciaKm'] = round($this->distanciaKm, 2);
        }

        return $datos;
    }
}
