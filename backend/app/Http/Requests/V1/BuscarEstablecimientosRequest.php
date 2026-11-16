<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Domains\Compartido\Domain\ValueObjects\Coordenadas;

final class BuscarEstablecimientosRequest extends ConsultaRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'q'         => ['sometimes', 'string', 'max:80'],
            'municipio' => ['sometimes', 'string', 'max:60'],
            'lat'       => ['required_with:lon', 'numeric', 'between:-90,90'],
            'lon'       => ['required_with:lat', 'numeric', 'between:-180,180'],
            'radioKm'   => ['sometimes', 'numeric', 'min:0.1', 'max:50'],
            'pagina'    => ['sometimes', 'integer', 'min:1'],
            'tamano'    => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function texto(): ?string     { return $this->textoOpcional('q'); }
    public function municipio(): ?string { return $this->textoOpcional('municipio'); }
    public function radioKm(): ?float    { return $this->numeroOpcional('radioKm'); }

    public function cerca(): ?Coordenadas
    {
        $lat = $this->numeroOpcional('lat');
        $lon = $this->numeroOpcional('lon');

        return $lat === null || $lon === null ? null : Coordenadas::de($lat, $lon);
    }
}
