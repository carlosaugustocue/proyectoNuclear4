<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Comercial;

use Illuminate\Foundation\Http\FormRequest;

final class SolicitarPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la verificación la hace un moderador (ADR-0022)
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'establecimientoId' => ['required', 'uuid', 'exists:establecimientos,id'],
            'nombreContacto'    => ['required', 'string', 'max:120'],
            'telefonoContacto'  => ['nullable', 'string', 'max:30'],
        ];
    }
}
