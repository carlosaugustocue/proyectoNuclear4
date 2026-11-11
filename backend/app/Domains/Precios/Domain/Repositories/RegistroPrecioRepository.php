<?php

declare(strict_types=1);

namespace App\Domains\Precios\Domain\Repositories;

use App\Domains\Precios\Domain\Entities\RegistroPrecio;

interface RegistroPrecioRepository
{
    public function save(RegistroPrecio $registro): void;
}
