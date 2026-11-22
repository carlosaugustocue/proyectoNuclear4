<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Domain\Repositories;

use App\Domains\Moderacion\Domain\ValueObjects\AccionModeracion;
use App\Domains\Moderacion\Domain\ValueObjects\CasoModeracion;

interface CasosModeracion
{
    /** Abre un caso si no existe uno del mismo origen para la observación. */
    public function abrir(string $observacionId, string $origen, string $detalle): void;

    /** @return array{items: list<CasoModeracion>, total: int} los más antiguos primero (FIFO) */
    public function listar(string $estado, int $pagina, int $tamano): array;

    public function obtener(string $casoId): ?CasoModeracion;

    /** Marca el caso RESUELTO y agrega la decisión a la bitácora, en una transacción. */
    public function resolver(string $casoId, AccionModeracion $accion, string $justificacion, string $moderador): void;
}
