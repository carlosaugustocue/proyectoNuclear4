<?php

declare(strict_types=1);

namespace App\Domains\Ingesta\Domain\Services;

use App\Domains\Ingesta\Domain\Exceptions\FilaSipsaInvalidaException;
use App\Domains\Ingesta\Domain\ValueObjects\ObservacionMayorista;
use DateTimeImmutable;

/**
 * Capa anticorrupción de SIPSA-DANE (RF-25, RF-26, ADR-0010, U-11).
 *
 * Traduce una fila del anexo de precios mayoristas al modelo de la plataforma, sin dejar que el
 * vocabulario de SIPSA (grupos, «precio promedio por kilogramo») entre al dominio:
 *  - grupo → categoría del catálogo y tipo de vigencia (RN-04: perecedero 3 días, abarrote 7);
 *  - unidad base según la nota al pie del DANE: «los precios reportados para los huevos y el bocadillo
 *    son $/unidad y los del aceite, el vinagre y el jugo de frutas $/litro»; el resto, $/kilogramo.
 */
final class TraductorSipsa
{
    /** @var array<string, array{0: string, 1: 'PERECEDERO'|'ABARROTE'}> grupo SIPSA → [categoría, vigencia] */
    private const GRUPOS = [
        'VERDURAS Y HORTALIZAS'         => ['Frutas y verduras', 'PERECEDERO'],
        'FRUTAS'                        => ['Frutas y verduras', 'PERECEDERO'],
        'TUBERCULOS, RAICES Y PLATANOS' => ['Frutas y verduras', 'PERECEDERO'],
        'GRANOS Y CEREALES'             => ['Granos', 'ABARROTE'],
        'LACTEOS Y HUEVOS'              => ['Lácteos', 'PERECEDERO'],
        'CARNES'                        => ['Carnes', 'PERECEDERO'],
        'PESCADOS'                      => ['Pescados', 'PERECEDERO'],
        'PROCESADOS'                    => ['Despensa', 'ABARROTE'],
    ];

    private const COLUMNAS = ['fecha', 'grupo', 'producto', 'codigo_cpc', 'mercado', 'precio'];

    /** @param array<string, string|null> $fila */
    public static function traducir(array $fila): ObservacionMayorista
    {
        foreach (self::COLUMNAS as $columna) {
            if (! array_key_exists($columna, $fila)) {
                throw new FilaSipsaInvalidaException("Falta la columna «{$columna}»");
            }
        }

        $grupo = mb_strtoupper(trim((string) $fila['grupo']));
        if (! isset(self::GRUPOS[$grupo])) {
            throw new FilaSipsaInvalidaException("El grupo «{$grupo}» no está en la tabla de traducción");
        }

        $producto = trim((string) preg_replace('/\s+/u', ' ', (string) $fila['producto']));
        if ($producto === '') {
            throw new FilaSipsaInvalidaException('El producto está vacío');
        }

        $mercado = trim((string) $fila['mercado']);
        if ($mercado === '') {
            throw new FilaSipsaInvalidaException('El mercado está vacío');
        }

        [$categoria, $vigencia] = self::GRUPOS[$grupo];

        return new ObservacionMayorista(
            producto:     $producto,
            codigoCpc:    trim((string) $fila['codigo_cpc']),
            categoria:    self::categoria($producto, $categoria),
            tipoVigencia: self::vigencia($producto, $vigencia),
            unidadBase:   self::unidadBase($producto),
            mercado:      $mercado,
            mes:          self::mes((string) $fila['fecha']),
            precio:       self::precio((string) $fila['precio']),
        );
    }

    private static function categoria(string $producto, string $porGrupo): string
    {
        $p = self::normalizar($producto);

        return match (true) {
            str_starts_with($p, 'aceite')                                   => 'Aceites',
            str_starts_with($p, 'cafe'), str_starts_with($p, 'chocolate')   => 'Bebidas',
            default                                                         => $porGrupo,
        };
    }

    /** @param 'PERECEDERO'|'ABARROTE' $porGrupo
     *  @return 'PERECEDERO'|'ABARROTE' */
    private static function vigencia(string $producto, string $porGrupo): string
    {
        return str_contains(self::normalizar($producto), 'en polvo') ? 'ABARROTE' : $porGrupo;
    }

    /** @return 'KG'|'L'|'UNIDAD' */
    private static function unidadBase(string $producto): string
    {
        $p = self::normalizar($producto);

        return match (true) {
            str_starts_with($p, 'huevo'), str_starts_with($p, 'bocadillo')                         => 'UNIDAD',
            str_starts_with($p, 'aceite'), str_starts_with($p, 'vinagre'), str_starts_with($p, 'jugo de fruta') => 'L',
            default                                                                                => 'KG',
        };
    }

    private static function mes(string $fecha): DateTimeImmutable
    {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', trim($fecha));
        if ($d === false || $d->format('Y-m-d') !== trim($fecha)) {
            throw new FilaSipsaInvalidaException("La fecha «{$fecha}» no tiene el formato AAAA-MM-DD");
        }

        return $d->modify('first day of this month');
    }

    private static function precio(string $precio): string
    {
        $precio = trim($precio);
        if (preg_match('/^\d+(\.\d+)?$/', $precio) !== 1 || preg_match('/^0+(\.0+)?$/', $precio) === 1) {
            throw new FilaSipsaInvalidaException("El precio «{$precio}» no es un número positivo");
        }

        return $precio;
    }

    private static function normalizar(string $texto): string
    {
        $sinTildes = strtr(mb_strtolower($texto), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);

        return $sinTildes;
    }
}
