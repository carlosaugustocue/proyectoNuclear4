<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

final class BuscarProductosRequest extends ConsultaRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'q'         => ['sometimes', 'string', 'min:2', 'max:80'],
            'ean'       => ['sometimes', 'regex:/^[0-9]{8,14}$/'],
            'categoria' => ['sometimes', 'string', 'max:40'],
            'pagina'    => ['sometimes', 'integer', 'min:1'],
            'tamano'    => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function texto(): ?string     { return $this->textoOpcional('q'); }
    public function ean(): ?string       { return $this->textoOpcional('ean'); }
    public function categoria(): ?string { return $this->textoOpcional('categoria'); }
}
