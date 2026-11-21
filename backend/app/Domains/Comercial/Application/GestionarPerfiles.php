<?php

declare(strict_types=1);

namespace App\Domains\Comercial\Application;

use App\Domains\Comercial\Domain\Entities\PerfilComercial;
use App\Domains\Comercial\Domain\Exceptions\PerfilNoEncontradoException;
use App\Domains\Comercial\Domain\Repositories\PerfilesComerciales;
use App\Domains\Establecimientos\Application\ConsultarDirectorio;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/** RF-31 y ADR-0022: solicitud, verificación, revocación del perfil y autenticación por credencial. */
final class GestionarPerfiles
{
    private const LONGITUD_CREDENCIAL = 40;

    public function __construct(
        private readonly PerfilesComerciales $perfiles,
        private readonly ConsultarDirectorio $directorio,
    ) {}

    public function solicitar(string $establecimientoId, string $nombreContacto, ?string $telefonoContacto): PerfilComercial
    {
        if ($this->directorio->porId($establecimientoId) === null) {
            throw new InvalidArgumentException('El establecimiento no existe en el directorio.');
        }

        $perfil = PerfilComercial::solicitar(Uuid::uuid7()->toString(), $establecimientoId);
        $this->perfiles->registrar($perfil, $nombreContacto, $telefonoContacto);

        return $perfil;
    }

    /** @return array{perfil: PerfilComercial, credencial: string} la credencial se entrega una sola vez */
    public function verificar(string $perfilId): array
    {
        $verificado = $this->existente($perfilId)->verificar();
        $credencial = bin2hex(random_bytes(self::LONGITUD_CREDENCIAL / 2));
        $this->perfiles->actualizar($verificado, self::hash($credencial));

        return ['perfil' => $verificado, 'credencial' => $credencial];
    }

    public function revocar(string $perfilId): PerfilComercial
    {
        $revocado = $this->existente($perfilId)->revocar();
        $this->perfiles->actualizar($revocado);

        return $revocado;
    }

    public function autenticar(string $credencial): ?PerfilComercial
    {
        return strlen($credencial) === self::LONGITUD_CREDENCIAL
            ? $this->perfiles->porCredencialHash(self::hash($credencial))
            : null;
    }

    private function existente(string $perfilId): PerfilComercial
    {
        return $this->perfiles->porId($perfilId) ?? throw new PerfilNoEncontradoException('El perfil comercial no existe.');
    }

    private static function hash(string $credencial): string
    {
        return hash('sha256', $credencial);
    }
}
