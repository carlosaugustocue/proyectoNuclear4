<?php

declare(strict_types=1);

use App\Domains\Precios\Infrastructure\Listeners\PublicarPrecioRegistrado;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;

describe('Outbox — PrecioRegistrado', function () {

    it('encola un job en domain-events al registrar un precio', function () {
        Queue::fake();

        $this->postJson('/api/v1/precios', [
            'producto_id'        => 'prod-panela-500g',
            'establecimiento_id' => 'est-mercado-armenia',
            'monto'              => '1800',
            'latitud'            => 4.5339,
            'longitud'           => -75.6811,
        ])->assertStatus(201);

        // Laravel envuelve los listeners queued en CallQueuedListener
        Queue::assertPushedOn(
            'domain-events',
            CallQueuedListener::class,
            fn ($job) => $job->class === PublicarPrecioRegistrado::class
        );
    });

    it('encola exactamente un job por precio registrado', function () {
        Queue::fake();

        $this->postJson('/api/v1/precios', [
            'producto_id'        => 'prod-leche-1l',
            'establecimiento_id' => 'est-olimpica-armenia',
            'monto'              => '2800',
            'latitud'            => 4.5280,
            'longitud'           => -75.6742,
        ])->assertStatus(201);

        Queue::assertPushed(CallQueuedListener::class, 1);
    });

    it('el job lleva el evento con el producto correcto', function () {
        Queue::fake();

        $this->postJson('/api/v1/precios', [
            'producto_id'        => 'prod-arroz-500g',
            'establecimiento_id' => 'est-exito-armenia',
            'monto'              => '3250',
            'latitud'            => 4.5339,
            'longitud'           => -75.6811,
        ])->assertStatus(201);

        Queue::assertPushedOn(
            'domain-events',
            CallQueuedListener::class,
            function ($job) {
                /** @var \App\Domains\Precios\Domain\Events\PrecioRegistrado $evento */
                $evento = $job->data[0] ?? null;
                return $job->class === PublicarPrecioRegistrado::class
                    && $evento instanceof \App\Domains\Precios\Domain\Events\PrecioRegistrado
                    && $evento->productoId === 'prod-arroz-500g'
                    && $evento->monto === '3250.00';
            }
        );
    });

})->group('feature');
