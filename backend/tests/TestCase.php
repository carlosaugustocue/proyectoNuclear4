<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Las pruebas recorren el flujo de eventos completo (PrecioRegistrado → ConfianzaEvaluada →
        // proyección) sin worker. Se fija aquí porque docker compose define QUEUE_CONNECTION=database
        // y esa variable de entorno tendría prioridad sobre phpunit.xml. Queue::fake() sigue funcionando.
        config(['queue.default' => 'sync']);
    }
}
