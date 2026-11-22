<?php

declare(strict_types=1);

namespace App\Domains\Confianza\Application;

use App\Domains\Confianza\Domain\Repositories\EvaluacionesRepositorio;
use App\Domains\Confianza\Domain\Services\PoliticaReputacion;
use App\Domains\Confianza\Domain\ValueObjects\CuentaContribuidora;
use App\Domains\Confianza\Domain\ValueObjects\PermisoAporte;
use App\Domains\Precios\Domain\Repositories\ObservacionesLectura;
use DateTimeImmutable;

/** RN-07: reputación de un contribuidor. La usan la evaluación de confianza y «mis aportes» (RF-12). */
final class ConsultarReputacion
{
    public function __construct(
        private readonly ObservacionesLectura    $observaciones,
        private readonly EvaluacionesRepositorio $evaluaciones,
    ) {}

    public function cuenta(string $reportadoPor): CuentaContribuidora
    {
        return new CuentaContribuidora(
            id:                 $reportadoPor,
            creadaEn:           $this->observaciones->primerAporteDe($reportadoPor) ?? new DateTimeImmutable(),
            aportesConfirmados: $this->evaluaciones->aportesConfirmadosDe($reportadoPor),
            suspendida:         false, // una cuenta suspendida no llega aquí: no puede autenticarse (ADR-0025)
        );
    }

    /** @return array{aportesConfirmados: int, requiereCorroboracion: bool} */
    public function resumen(string $reportadoPor): array
    {
        $cuenta = $this->cuenta($reportadoPor);

        return [
            'aportesConfirmados'    => $cuenta->aportesConfirmados,
            'requiereCorroboracion' => PoliticaReputacion::evaluar($cuenta) !== PermisoAporte::PUEDE_ACTUALIZAR_VIGENTE,
        ];
    }
}
