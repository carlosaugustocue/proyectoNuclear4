<?php

declare(strict_types=1);

namespace App\Domains\Moderacion\Application;

use App\Domains\Moderacion\Domain\Repositories\CasosModeracion;
use App\Domains\Moderacion\Domain\ValueObjects\CasoModeracion;

/** RF-20: cola de casos, los más antiguos primero. */
final class ConsultarCasos
{
    public function __construct(private readonly CasosModeracion $casos) {}

    /** @return array{items: list<CasoModeracion>, total: int} */
    public function listar(string $estado, int $pagina, int $tamano): array
    {
        return $this->casos->listar($estado, $pagina, $tamano);
    }
}
