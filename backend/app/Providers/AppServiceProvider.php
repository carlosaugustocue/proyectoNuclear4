<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domains\Precios\Domain\Repositories\RegistroPrecioRepository;
use App\Domains\Precios\Infrastructure\Persistence\EloquentRegistroPrecioRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ── Dominio Precios ───────────────────────────────────────────────
        $this->app->bind(
            RegistroPrecioRepository::class,
            EloquentRegistroPrecioRepository::class,
        );
    }

    public function boot(): void {}
}
