<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Comercial;

use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;

/** Forma de la petición; las reglas de negocio (RN-14) las aplica ValidadorPromocion. */
final class PromocionRequest extends FormRequest
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
            'productoId'        => ['required', 'uuid'],
            'tipo'              => ['required', 'string', 'max:30'],
            'valor'             => ['nullable', 'numeric'],
            'pague'             => ['nullable', 'integer'],
            'lleve'             => ['nullable', 'integer'],
            'inicio'            => ['required', 'date'],
            'fin'               => ['required', 'date'],
        ];
    }

    public function valor(): ?string
    {
        return $this->filled('valor') ? (string) $this->input('valor') : null;
    }

    public function entero(string $campo): ?int
    {
        return $this->filled($campo) ? $this->integer($campo) : null;
    }

    public function fecha(string $campo): DateTimeImmutable
    {
        return new DateTimeImmutable($this->string($campo)->toString());
    }
}
