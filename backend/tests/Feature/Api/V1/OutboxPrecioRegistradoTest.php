<?php

declare(strict_types=1);

use App\Domains\Precios\Domain\Events\PrecioRegistrado;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/*
 * ADR-0003: el registro y el evento se guardan en la misma transacción (tabla jobs
 * como outbox); el worker los procesa en la cola domain-events.
 */

describe('Outbox — PrecioRegistrado', function () {
    beforeEach(function () {
        $productoId = crearProducto();
        $this->cuerpo = [
            'presentacionId'    => crearPresentacion($productoId),
            'establecimientoId' => crearEstablecimiento(),
            'precio'            => '3250',
            'observadoEn'       => now()->subMinute()->toIso8601String(),
        ];
    });

    it('encola en domain-events los listeners del evento al registrar un reporte', function () {
        Queue::fake();

        $this->withHeaders(['Idempotency-Key' => (string) Str::uuid()])
            ->postJson('/v1/reportes-precio', $this->cuerpo)->assertStatus(202);

        Queue::assertPushedOn('domain-events', CallQueuedListener::class);
    });

    it('el evento encolado lleva la presentación y el monto exactos', function () {
        Queue::fake();

        $this->withHeaders(['Idempotency-Key' => (string) Str::uuid()])
            ->postJson('/v1/reportes-precio', $this->cuerpo)->assertStatus(202);

        Queue::assertPushed(CallQueuedListener::class, function (CallQueuedListener $job) {
            $evento = $job->data[0] ?? null;

            return $evento instanceof PrecioRegistrado
                && $evento->presentacionId === $this->cuerpo['presentacionId']
                && $evento->monto === '3250.00';
        });
    });

    it('un reintento idempotente no vuelve a encolar el evento', function () {
        Queue::fake();
        $clave = (string) Str::uuid();

        $this->withHeaders(['Idempotency-Key' => $clave])->postJson('/v1/reportes-precio', $this->cuerpo);
        $antes = Queue::pushed(CallQueuedListener::class)->count();
        $this->withHeaders(['Idempotency-Key' => $clave])->postJson('/v1/reportes-precio', $this->cuerpo)->assertOk();

        expect(Queue::pushed(CallQueuedListener::class)->count())->toBe($antes);
    });
})->group('feature');
