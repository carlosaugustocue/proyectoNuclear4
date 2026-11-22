<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Identidad\Application\GestionarCuentas;
use App\Domains\Identidad\Domain\Exceptions\CredencialesInvalidasException;
use Illuminate\Console\Command;

/**
 * RF-24 / ADR-0025: el equipo da el rol MODERADOR a una cuenta ya registrada, después de
 * confirmar fuera de banda que el correo es de esa persona (no hay verificación por correo).
 * Uso: php artisan identidad:moderador persona@ejemplo.com
 */
final class PromoverModerador extends Command
{
    protected $signature   = 'identidad:moderador {correo : correo de una cuenta ya registrada}';
    protected $description = 'Da el rol MODERADOR a una cuenta existente';

    public function handle(GestionarCuentas $cuentas): int
    {
        try {
            $cuenta = $cuentas->promoverAModerador((string) $this->argument('correo'));
        } catch (CredencialesInvalidasException|\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info("{$cuenta->seudonimo()} ahora es MODERADOR.");

        return self::SUCCESS;
    }
}
