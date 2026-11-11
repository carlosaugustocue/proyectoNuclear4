<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

final class RegistrarPrecioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth se implementa en iteración siguiente
    }

    /** @return array<string, list<string|\Illuminate\Contracts\Validation\Rule>> */
    public function rules(): array
    {
        return [
            'producto_id'        => ['required', 'string', 'max:36'],
            'establecimiento_id' => ['required', 'string', 'max:36'],
            'monto'              => ['required', 'numeric', 'gt:0'],
            'moneda'             => ['sometimes', 'string', 'size:3'],
            'latitud'            => ['required', 'numeric', 'between:-90,90'],
            'longitud'           => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
