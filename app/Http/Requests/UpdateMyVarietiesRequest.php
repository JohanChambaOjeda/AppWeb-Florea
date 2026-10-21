<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMyVarietiesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $min = (int) config('florea.custom_length.min');
        $max = (int) config('florea.custom_length.max');

        return [
            'varieties' => ['required', 'array', 'min:1'],
            'varieties.*.variety_id' => ['required', 'integer', 'distinct', 'exists:varieties,id,is_active,1'],
            'varieties.*.lengths' => ['required', 'array', 'min:1'],
            // Sin "distinct": en una ruta anidada como varieties.*.lengths.*
            // Laravel compara TODOS los largos de TODAS las variedades, así
            // que sembrar Freedom 60 y Explorer 60 salía como duplicado. Los
            // repetidos dentro de una variedad los limpia chosenVarieties().
            'varieties.*.lengths.*' => ['required', 'integer', "between:{$min},{$max}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $min = (int) config('florea.custom_length.min');
        $max = (int) config('florea.custom_length.max');

        return [
            'varieties.required' => 'Marca al menos una variedad.',
            'varieties.min' => 'Marca al menos una variedad.',
            'varieties.*.lengths.required' => 'Elige al menos un largo.',
            'varieties.*.lengths.min' => 'Elige al menos un largo.',
            'varieties.*.lengths.*.between' => "El largo debe estar entre {$min} y {$max} cm.",
            'varieties.*.variety_id.exists' => 'Esa variedad ya no está disponible.',
        ];
    }

    /**
     * Lo marcado, listo para sync(): variety_id => ['lengths' => [...]].
     * Los largos salen ordenados y sin repetidos.
     *
     * @return array<int, array{lengths: list<int>}>
     */
    public function chosenVarieties(): array
    {
        $chosen = [];

        foreach ((array) $this->validated('varieties') as $item) {
            if (! is_array($item)) {
                continue;
            }

            $lengths = array_values(array_unique(array_map(intval(...), (array) $item['lengths'])));
            sort($lengths);

            $chosen[(int) $item['variety_id']] = ['lengths' => $lengths];
        }

        return $chosen;
    }
}
