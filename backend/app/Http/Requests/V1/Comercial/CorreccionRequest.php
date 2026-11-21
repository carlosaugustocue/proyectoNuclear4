<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Comercial;

use Illuminate\Foundation\Http\FormRequest;

final class CorreccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'establecimientoId' => ['required', 'uuid'],
            'detalle'           => ['required', 'string', 'min:10', 'max:150'],
        ];
    }
}
