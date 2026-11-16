<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Http\Problemas\SolicitudInvalidaException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base para solicitudes GET: un parámetro de consulta inválido es un 400 (contrato),
 * no un 422 como ocurre con los cuerpos JSON.
 */
abstract class ConsultaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        /** @var array<string, list<string>> $errores */
        $errores = $validator->errors()->toArray();
        throw new SolicitudInvalidaException('Parámetros de consulta inválidos', $errores);
    }

    public function pagina(): int
    {
        return max(1, (int) $this->query('pagina', '1'));
    }

    public function tamano(): int
    {
        return min(50, max(1, (int) $this->query('tamano', '20')));
    }

    protected function textoOpcional(string $clave): ?string
    {
        $valor = $this->query($clave);

        return is_string($valor) && trim($valor) !== '' ? trim($valor) : null;
    }

    protected function numeroOpcional(string $clave): ?float
    {
        $valor = $this->query($clave);

        return is_numeric($valor) ? (float) $valor : null;
    }
}
