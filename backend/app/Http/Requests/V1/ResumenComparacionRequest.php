<?php

declare(strict_types=1);

namespace App\Http\Requests\V1;

final class ResumenComparacionRequest extends ConsultaRequest
{
    private const MAXIMO = 50;

    protected function prepareForValidation(): void
    {
        $ids = array_values(array_filter(array_map('trim', explode(',', (string) $this->query('productoIds', '')))));
        $this->merge(['ids' => $ids]);
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'ids'   => ['required', 'array', 'min:1', 'max:' . self::MAXIMO],
            'ids.*' => ['uuid'],
        ];
    }

    /** @return list<string> */
    public function productoIds(): array
    {
        /** @var list<string> $ids */
        $ids = $this->input('ids', []);

        return array_values(array_unique($ids));
    }
}
