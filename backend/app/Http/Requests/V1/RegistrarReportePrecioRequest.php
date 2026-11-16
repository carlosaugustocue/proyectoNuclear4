<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Http\Problemas\SolicitudInvalidaException;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Cuerpo ReportePrecioNuevo (openapi.yaml), additionalProperties: false. */
final class RegistrarReportePrecioRequest extends FormRequest
{
    private const CAMPOS = ['presentacionId', 'establecimientoId', 'precio', 'observadoEn', 'fotoId'];

    public function authorize(): bool
    {
        return true; // El contexto Identidad (JWT) llega en el corte 3; ver ADR-0014.
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
            'establecimientoId' => ['required', 'uuid', 'exists:establecimientos,id'],
            'precio'            => ['required', 'string', 'regex:/^[0-9]+(\.[0-9]{1,2})?$/', 'not_regex:/^0+(\.0+)?$/'],
            'observadoEn'       => ['required', 'date'],
            'fotoId'            => ['sometimes', 'uuid'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $extra = array_diff(array_keys($this->all()), self::CAMPOS);
            foreach ($extra as $campo) {
                $v->errors()->add((string) $campo, "El campo {$campo} no forma parte del contrato.");
            }
        });
    }

    public function claveIdempotencia(): string
    {
        return strtolower((string) $this->header('Idempotency-Key'));
    }

    public function observadoEn(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->string('observadoEn')->toString());
    }
}
