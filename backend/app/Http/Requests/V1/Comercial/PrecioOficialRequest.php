<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Comercial;

use App\Http\Problemas\SolicitudInvalidaException;
use Illuminate\Foundation\Http\FormRequest;

final class PrecioOficialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // AutenticarComercio + RN-15 en el caso de uso
    }

    protected function prepareForValidation(): void
    {
        $clave = $this->header('Idempotency-Key');
        if (! is_string($clave) || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $clave) !== 1) {
            throw new SolicitudInvalidaException('La cabecera Idempotency-Key es obligatoria y debe ser un UUID');
        }
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'presentacionId'    => ['required', 'uuid', 'exists:presentaciones,id'],
            'establecimientoId' => ['required', 'uuid'],
            'precio'            => ['required', 'string', 'regex:/^[0-9]+(\.[0-9]{1,2})?$/', 'not_regex:/^0+(\.0+)?$/'],
        ];
    }

    public function claveIdempotencia(): string
    {
        return strtolower((string) $this->header('Idempotency-Key'));
    }
}
