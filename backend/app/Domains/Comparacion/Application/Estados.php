<?php

declare(strict_types=1);

namespace App\Domains\Comparacion\Application;

use App\Domains\Confianza\Domain\ValueObjects\CategoriaProducto;
use App\Domains\Confianza\Domain\ValueObjects\EstadoConfianza;
use App\Domains\Confianza\Domain\ValueObjects\PermisoAporte;

/** Traduce los nombres persistidos/publicados a los enums del dominio de Confianza. */
final class Estados
{
    public static function confianza(string $nombre): EstadoConfianza
    {
        foreach (EstadoConfianza::cases() as $caso) {
            if ($caso->name === $nombre) {
                return $caso;
            }
        }

        return EstadoConfianza::NO_CORROBORADO;
    }

    public static function permiso(string $nombre): PermisoAporte
    {
        foreach (PermisoAporte::cases() as $caso) {
            if ($caso->name === $nombre) {
                return $caso;
            }
        }

        return PermisoAporte::REQUIERE_CORROBORACION;
    }

    public static function categoria(string $tipoVigencia): CategoriaProducto
    {
        return $tipoVigencia === 'PERECEDERO' ? CategoriaProducto::PERECEDERO : CategoriaProducto::ABARROTE;
    }
}
