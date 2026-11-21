<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Application;

use App\Domains\Catalogo\Application\ConsultarCatalogo;
use App\Domains\Comercial\Domain\Entities\PerfilComercial;
use App\Domains\Comercial\Domain\Repositories\Promociones;
use App\Domains\Comercial\Domain\Services\PoliticaPublicacion;
use App\Domains\Comercial\Domain\Services\ValidadorPromocion;
use App\Domains\Moderacion\Application\AbrirCasoPorCorreccion;
use App\Domains\Precios\Application\Commands\RegistrarReportePrecioCommand;
use App\Domains\Precios\Application\Handlers\RegistrarReportePrecioHandler;
use App\Domains\Precios\Application\Handlers\ResultadoRegistro;
use App\Domains\Precios\Domain\ValueObjects\FuenteObservacion;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * Publicaciones de un comercio verificado (RF-32 a RF-34). Todas pasan por RN-15 y usan solo
 * interfaces públicas del núcleo (ADR-0023): Precios para el precio oficial, Moderación para las correcciones.
 */
final class PublicarComoComercio
{
    public function __construct(
        private readonly RegistrarReportePrecioHandler $registrarPrecio,
        private readonly AbrirCasoPorCorreccion        $abrirCaso,
        private readonly Promociones                   $promociones,
        private readonly ConsultarCatalogo             $catalogo,
    ) {}

    /** ADR-0015: el precio oficial entra por el mismo flujo de confianza que un reporte. */
    public function precioOficial(PerfilComercial $perfil, string $presentacionId, string $establecimientoId, string $precio, string $clave): ResultadoRegistro
    {
        PoliticaPublicacion::autorizar($perfil, $establecimientoId);

        return $this->registrarPrecio->handle(new RegistrarReportePrecioCommand(
            presentacionId:    $presentacionId,
            establecimientoId: $establecimientoId,
            precio:            $precio,
            observadoEn:       new DateTimeImmutable(),
            reportadoPor:      self::autorDe($perfil),
            claveIdempotencia: $clave,
            fuente:            FuenteObservacion::COMERCIO,
        ));
    }

    /** @return array{promocionId: string, descripcion: string} */
    public function promocion(
        PerfilComercial   $perfil,
        string            $establecimientoId,
        string            $productoId,
        string            $tipo,
        ?string           $valor,
        ?int              $pague,
        ?int              $lleve,
        DateTimeImmutable $inicio,
        DateTimeImmutable $fin,
    ): array {
        PoliticaPublicacion::autorizar($perfil, $establecimientoId);
        if ($this->catalogo->resumen($productoId) === null) {
            throw new InvalidArgumentException('El producto no existe en el catálogo.');
        }

        $promocion = ValidadorPromocion::validar($tipo, $valor, $pague, $lleve, $inicio, $fin);
        $id = Uuid::uuid7()->toString();
        $this->promociones->guardar($id, $perfil->id, $productoId, $promocion);

        return ['promocionId' => $id, 'descripcion' => $promocion->descripcion()];
    }

    public function retirarPromocion(PerfilComercial $perfil, string $promocionId): bool
    {
        PoliticaPublicacion::autorizar($perfil, $perfil->establecimientoId);

        return $this->promociones->retirar($promocionId, $perfil->id, new DateTimeImmutable());
    }

    public function correccion(PerfilComercial $perfil, string $establecimientoId, string $detalle): string
    {
        PoliticaPublicacion::autorizar($perfil, $establecimientoId);

        return $this->abrirCaso->abrir($establecimientoId, $detalle);
    }

    /** Autor interno del precio oficial para RN-07/RN-08 (cabe en reportado_por, 36 caracteres). */
    private static function autorDe(PerfilComercial $perfil): string
    {
        return 'comercio-' . substr(str_replace('-', '', $perfil->id), 0, 24);
    }
}
