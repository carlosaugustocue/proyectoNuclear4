<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Infrastructure;

use App\Domains\Comercial\Domain\Repositories\Promociones;
use App\Domains\Comercial\Domain\Services\VisibilidadPromocion;
use App\Domains\Comercial\Domain\ValueObjects\PromocionRegistrada;
use App\Domains\Comparacion\Application\PromocionesVigentes;
use App\Domains\Establecimientos\Application\ConsultarDirectorio;
use DateTimeImmutable;

/**
 * Comercial implementa el puerto que declara Comparación (inversión de dependencia, ADR-0023).
 * Solo expone datos públicos de la promoción: nunca el perfil ni su contacto (EC-07).
 */
final class PromocionesVigentesComercial implements PromocionesVigentes
{
    public function __construct(
        private readonly Promociones         $promociones,
        private readonly ConsultarDirectorio $directorio,
    ) {}

    public function deProductos(array $productoIds): array
    {
        $ahora = new DateTimeImmutable();
        $visibles = array_filter(
            $this->promociones->candidatasDeProductos($productoIds, $ahora),
            static fn (PromocionRegistrada $p): bool => VisibilidadPromocion::esVisible($p->promocion, $p->estadoPerfil, $p->retiradaEn, $ahora),
        );

        $resultado = [];
        foreach ($visibles as $p) {
            $establecimiento = $this->directorio->porId($p->establecimientoId);
            if ($establecimiento === null) {
                continue;
            }
            $resultado[] = [
                'tipo'            => $p->promocion->tipo->value,
                'descripcion'     => $p->promocion->descripcion(),
                'establecimiento' => ['id' => $establecimiento->id, 'nombre' => $establecimiento->nombre],
                'productoId'      => $p->productoId,
                'vigenteHasta'    => $p->promocion->fin->format(DATE_ATOM),
                'esPromocion'     => true,
            ];
        }

        return $resultado;
    }
}
