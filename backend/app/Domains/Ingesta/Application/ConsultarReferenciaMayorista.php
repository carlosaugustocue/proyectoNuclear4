<?php

declare(strict_types=1);

namespace App\Domains\Ingesta\Application;

use App\Domains\Catalogo\Domain\Repositories\CatalogoLectura;
use App\Domains\Ingesta\Domain\Repositories\ReferenciasMayoristas;

/** Referencia mayorista de un producto (RF-25), siempre separada de la comparación de tiendas (ADR-0010). */
final class ConsultarReferenciaMayorista
{
    public const FUENTE = [
        'nombre'      => 'SIPSA – DANE',
        'descripcion' => 'Sistema de Información de Precios y Abastecimiento del Sector Agropecuario, precios mayoristas',
        'url'         => 'https://www.dane.gov.co/index.php/estadisticas-por-tema/agropecuario/sistema-de-informacion-de-precios-sipsa',
    ];

    public const ADVERTENCIA = 'Precio mayorista promedio en las centrales de abasto de Armenia. No es el precio de góndola de una tienda y no entra en la comparación.';

    public function __construct(
        private readonly CatalogoLectura $catalogo,
        private readonly ReferenciasMayoristas $referencias,
    ) {}

    /**
     * @return array{productoId: string, unidadBase: ?string, serie: list<array{mes: string, precio: string}>, ultimo: ?array{mes: string, precio: string}, mercados: list<string>, fuente: array<string, string>, advertencia: string}|null
     *         null si el producto no existe
     */
    public function consultar(string $productoId): ?array
    {
        if ($this->catalogo->resumen($productoId) === null) {
            return null;
        }

        $datos = $this->referencias->serieMensual($productoId);
        $ultimo = $datos['serie'] === [] ? null : $datos['serie'][array_key_last($datos['serie'])];

        return [
            'productoId'  => $productoId,
            'unidadBase'  => $datos['unidadBase'],
            'serie'       => $datos['serie'],
            'ultimo'      => $ultimo,
            'mercados'    => $datos['mercados'],
            'fuente'      => self::FUENTE,
            'advertencia' => self::ADVERTENCIA,
        ];
    }
}
