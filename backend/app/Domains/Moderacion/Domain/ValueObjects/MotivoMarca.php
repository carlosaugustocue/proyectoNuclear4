<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Domain\ValueObjects;

/** RF-19: por qué un usuario cree que un precio es dudoso. */
enum MotivoMarca: string
{
    case PRECIO_INCORRECTO          = 'PRECIO_INCORRECTO';
    case PRODUCTO_EQUIVOCADO        = 'PRODUCTO_EQUIVOCADO';
    case ESTABLECIMIENTO_EQUIVOCADO = 'ESTABLECIMIENTO_EQUIVOCADO';
    case DESACTUALIZADO             = 'DESACTUALIZADO';
    case OTRO                       = 'OTRO';

    public function etiqueta(): string
    {
        return match ($this) {
            self::PRECIO_INCORRECTO          => 'Precio incorrecto',
            self::PRODUCTO_EQUIVOCADO        => 'Producto equivocado',
            self::ESTABLECIMIENTO_EQUIVOCADO => 'Establecimiento equivocado',
            self::DESACTUALIZADO             => 'Desactualizado',
            self::OTRO                       => 'Otro motivo',
        };
    }
}
