<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Infrastructure\Listeners;

use App\Domains\Confianza\Domain\Events\ConfianzaEvaluada;
use App\Domains\Moderacion\Application\AbrirCasoPorAnomalia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class AbrirCasoAlEvaluarConfianza implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'domain-events';
    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(private readonly AbrirCasoPorAnomalia $casoDeUso) {}

    public function handle(ConfianzaEvaluada $evento): void
    {
        $this->casoDeUso->siCorresponde($evento);
    }
}
