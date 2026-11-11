<?php

declare(strict_types=1);

namespace App\Domains\Precios\Application\Handlers;

use App\Domains\Precios\Application\Commands\RegistrarPrecioCommand;
use App\Domains\Precios\Domain\Entities\RegistroPrecio;
use App\Domains\Precios\Domain\Repositories\RegistroPrecioRepository;
use App\Domains\Precios\Domain\ValueObjects\Coordenadas;
use App\Domains\Precios\Domain\ValueObjects\Precio;
use Illuminate\Contracts\Events\Dispatcher;

final class RegistrarPrecioHandler
{
    public function __construct(
        private readonly RegistroPrecioRepository $repository,
        private readonly Dispatcher               $events,
    ) {}

    public function handle(RegistrarPrecioCommand $command): string
    {
        $registro = RegistroPrecio::crear(
            productoId:        $command->productoId,
            establecimientoId: $command->establecimientoId,
            precio:            Precio::de($command->monto, $command->moneda),
            coordenadas:       Coordenadas::de($command->latitud, $command->longitud),
            reportadoPor:      $command->reportadoPor,
        );

        $this->repository->save($registro);

        // Despachar eventos de dominio al bus de Laravel (outbox vía jobs table)
        foreach ($registro->pullDomainEvents() as $evento) {
            $this->events->dispatch($evento);
        }

        return $registro->id();
    }
}
