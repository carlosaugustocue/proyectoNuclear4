<?php

declare(strict_types=1);

namespace App\Domains\Comparacion\Infrastructure\Listeners;

use App\Domains\Comparacion\Application\ProyectarComparacion;
use App\Domains\Confianza\Domain\Events\ConfianzaEvaluada;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/** Actualiza el modelo de lectura en el worker (ADR-0003, EC-02). */
final class ProyectarComparacionAlEvaluarConfianza implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'domain-events';
    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(private readonly ProyectarComparacion $casoDeUso) {}

    public function handle(ConfianzaEvaluada $evento): void
    {
        $this->casoDeUso->proyectar($evento);
    }
}
