<?php

declare(strict_types=1);

namespace App\Domains\Precios\Infrastructure\Listeners;

use App\Domains\Precios\Domain\Events\PrecioRegistrado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Listener queued — implementa el patrón Outbox Transaccional (ADR-0003).
 *
 * Al implementar ShouldQueue Laravel serializa el evento en la tabla `jobs`
 * dentro de la misma transacción de base de datos que el INSERT de
 * registros_precio, garantizando consistencia sin necesidad de un broker externo.
 *
 * El worker `queue:work --queue=domain-events` procesa estos jobs de forma asíncrona.
 */
final class PublicarPrecioRegistrado implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'domain-events';
    public int    $tries = 3;
    public int    $backoff = 5;

    public function handle(PrecioRegistrado $evento): void
    {
        // Por ahora registramos el evento en el log de Laravel.
        // En iteraciones siguientes este listener notificará al módulo
        // Confianza para calcular el score del contribuidor y al módulo
        // Comparación para actualizar la proyección de precios mínimos.
        logger()->info('PrecioRegistrado procesado por worker', [
            'registro_id'       => $evento->registroId,
            'producto_id'       => $evento->productoId,
            'establecimiento_id'=> $evento->establecimientoId,
            'monto'             => $evento->monto,
            'moneda'            => $evento->moneda,
            'reportado_por'     => $evento->reportadoPor,
            'occurred_on'       => $evento->occurredOn->format('c'),
        ]);
    }
}
