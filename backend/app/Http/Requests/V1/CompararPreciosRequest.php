<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Domains\Comparacion\Application\ConsultarComparacion;
use App\Domains\Compartido\Domain\ValueObjects\Coordenadas;
use Illuminate\Validation\Validator;

final class CompararPreciosRequest extends ConsultaRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'productoId'        => ['required', 'uuid'],
            'presentacionId'    => ['sometimes', 'uuid'],
            'municipio'         => ['sometimes', 'string', 'max:60'],
            'lat'               => ['required_with:lon', 'numeric', 'between:-90,90'],
            'lon'               => ['required_with:lat', 'numeric', 'between:-180,180'],
            'radioKm'           => ['sometimes', 'numeric', 'min:0.1', 'max:50'],
            'orden'             => ['sometimes', 'in:' . ConsultarComparacion::ORDEN_PRECIO . ',' . ConsultarComparacion::ORDEN_DISTANCIA],
            'incluirNoVigentes' => ['sometimes', 'in:true,false,1,0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($this->orden() === ConsultarComparacion::ORDEN_DISTANCIA && $this->cerca() === null) {
                $v->errors()->add('orden', 'Para ordenar por distancia se requieren lat y lon.');
            }
        });
    }

    public function productoId(): string      { return (string) $this->query('productoId'); }
    public function presentacionId(): ?string { return $this->textoOpcional('presentacionId'); }
    public function municipio(): ?string      { return $this->textoOpcional('municipio'); }
    public function radioKm(): float          { return $this->numeroOpcional('radioKm') ?? 3.0; }

    public function orden(): string
    {
        return $this->textoOpcional('orden') ?? ConsultarComparacion::ORDEN_PRECIO;
    }

    public function incluirNoVigentes(): bool
    {
        return in_array($this->query('incluirNoVigentes'), ['true', '1'], true);
    }

    public function cerca(): ?Coordenadas
    {
        $lat = $this->numeroOpcional('lat');
        $lon = $this->numeroOpcional('lon');

        return $lat === null || $lon === null ? null : Coordenadas::de($lat, $lon);
    }
}
