<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Infrastructure\Listeners;

use App\Domains\Confianza\Application\EvaluarConfianzaObservacion;
use App\Domains\Precios\Domain\Events\PrecioRegistrado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Consumidor del outbox (ADR-0003): al implementar ShouldQueue, Laravel guarda el job
 * en la tabla `jobs` dentro de la misma transacción que el INSERT de la observación.
 * El worker (`queue:work --queue=domain-events`) lo procesa de forma asíncrona.
 */
final class EvaluarConfianzaAlRegistrarPrecio implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'domain-events';
    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(private readonly EvaluarConfianzaObservacion $casoDeUso) {}

    public function handle(PrecioRegistrado $evento): void
    {
        $this->casoDeUso->evaluar($evento);
    }
}
