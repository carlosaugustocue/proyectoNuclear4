<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Application;

use App\Domains\Confianza\Domain\Repositories\EvaluacionesRepositorio;
use App\Domains\Moderacion\Domain\Exceptions\MarcaDuplicadaException;
use App\Domains\Moderacion\Domain\Exceptions\ObservacionNoEncontradaException;
use App\Domains\Moderacion\Domain\Repositories\CasosModeracion;
use App\Domains\Moderacion\Domain\Repositories\MarcasObservacion;
use App\Domains\Moderacion\Domain\ValueObjects\MotivoMarca;
use Illuminate\Database\ConnectionInterface;

/**
 * RF-19 (ADR-0026, ADR-0007 «marcar, no borrar»): la marca no cambia la comparación; abre (o se suma
 * a) un caso MARCA_USUARIO y decide un moderador. El caso nunca dice quién marcó (EC-07).
 */
final class MarcarObservacion
{
    public function __construct(
        private readonly EvaluacionesRepositorio $evaluaciones,
        private readonly MarcasObservacion       $marcas,
        private readonly CasosModeracion         $casos,
        private readonly ConnectionInterface     $db,
    ) {}

    public function marcar(string $observacionId, string $cuentaId, MotivoMarca $motivo, ?string $comentario): void
    {
        // Solo se marca lo que se muestra: una observación ya evaluada por Confianza.
        if ($this->evaluaciones->obtener($observacionId) === null) {
            throw new ObservacionNoEncontradaException('El precio no existe.');
        }
        $comentario = $comentario === null || trim($comentario) === '' ? null : trim($comentario);

        $this->db->transaction(function () use ($observacionId, $cuentaId, $motivo, $comentario): void {
            if (! $this->marcas->registrar($observacionId, $cuentaId, $motivo, $comentario)) {
                throw new MarcaDuplicadaException('Ya marcaste este precio; un moderador lo revisará.');
            }
            // Un solo caso por observación y origen: las marcas siguientes se suman al mismo.
            $this->casos->abrir($observacionId, 'MARCA_USUARIO',
                'Marcado como dudoso: ' . $motivo->etiqueta() . ($comentario !== null ? ". «{$comentario}»" : '.'));
        });
    }
}
