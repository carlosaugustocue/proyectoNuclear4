<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

use App\Domains\Canasta\Domain\Services\OrdenCanasta;
use App\Domains\Canasta\Domain\Services\ValidadorCanasta;
use App\Domains\Canasta\Domain\ValueObjects\ItemSolicitado;
use Illuminate\Foundation\Http\FormRequest;

/** Forma del cuerpo de POST /v1/canastas/comparacion; las reglas de negocio (RN-11) las aplica el dominio. */
final class CompararCanastaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Sin cuenta (ADR-0018); Identidad está diferida (ADR-0014).
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'items'              => ['present', 'array', 'max:' . ValidadorCanasta::MAX_PRODUCTOS],
            'items.*.productoId' => ['required', 'uuid'],
            'items.*.cantidad'   => ['required', 'string', 'regex:/^[0-9]+(\.[0-9]{1,3})?$/'],
            'items.*.unidad'     => ['required', 'string', 'in:g,kg,mL,L,unidad'],
            'orden'              => ['sometimes', 'string', 'in:' . OrdenCanasta::DISPONIBILIDAD . ',' . OrdenCanasta::TOTAL],
            'municipio'          => ['sometimes', 'string', 'max:60'],
        ];
    }

    /** @return list<ItemSolicitado> */
    public function items(): array
    {
        /** @var list<array{productoId: string, cantidad: string, unidad: string}> $items */
        $items = $this->validated('items');

        return array_map(static fn (array $i): ItemSolicitado => new ItemSolicitado($i['productoId'], $i['cantidad'], $i['unidad']), $items);
    }

    public function orden(): string
    {
        $orden = $this->validated('orden');

        return is_string($orden) ? $orden : OrdenCanasta::DISPONIBILIDAD;
    }

    public function municipio(): ?string
    {
        $municipio = $this->validated('municipio');

        return is_string($municipio) ? $municipio : null;
    }
}
