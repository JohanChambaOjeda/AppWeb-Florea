<?php

namespace App\Http\Requests;

use App\Enums\CutPoint;
use App\Enums\Destination;
use App\Enums\ListingKind;
use App\Enums\Unit;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreListingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $min = (int) config('florea.custom_length.min');
        $max = (int) config('florea.custom_length.max');

        return [
            'kind' => ['required', Rule::enum(ListingKind::class)],

            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.variety_id' => ['required', 'integer', 'exists:varieties,id,is_active,1'],
            'items.*.lengths' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.lengths.*' => ['required', 'integer', "between:{$min},{$max}", 'distinct'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.01', 'max:9999.99'],
            'items.*.unit' => ['required', Rule::enum(Unit::class)],

            'needed_by' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.$this->lastNeededByDate()],
            'destination' => ['nullable', Rule::enum(Destination::class)],
            'cut_point' => ['nullable', Rule::enum(CutPoint::class)],
            'notes' => ['nullable', 'string', 'max:'.config('florea.notes_max')],
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
            'items.required' => 'Agrega al menos una variedad.',
            'items.min' => 'Agrega al menos una variedad.',
            'items.max' => 'Son demasiadas líneas. Divide el pedido en dos publicaciones.',
            'items.*.variety_id.required' => 'Elige una variedad.',
            'items.*.variety_id.exists' => 'Esa variedad ya no está disponible.',
            'items.*.lengths.required' => 'Elige al menos un largo.',
            'items.*.lengths.min' => 'Elige al menos un largo.',
            'items.*.lengths.*.between' => "El largo debe estar entre {$min} y {$max} cm.",
            'items.*.quantity.min' => 'La cantidad debe ser mayor que cero.',
            'items.*.unit.required' => 'Elige la unidad.',
            'needed_by.required' => 'Dinos para cuándo la necesitas.',
            'needed_by.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'needed_by.before_or_equal' => 'Elige una fecha más cercana.',
        ];
    }

    /**
     * Dos líneas con la misma variedad y exactamente los mismos largos son
     * la misma línea repetida. Misma variedad con largos distintos sí vale
     * (por ejemplo Freedom 60 y Freedom 50 en cantidades diferentes).
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $seen = [];

                foreach ((array) $this->input('items', []) as $index => $item) {
                    if (! is_array($item) || ! isset($item['variety_id']) || ! is_array($item['lengths'] ?? null)) {
                        continue;
                    }

                    $lengths = array_map(intval(...), $item['lengths']);
                    sort($lengths);
                    $key = $item['variety_id'].':'.implode(',', $lengths);

                    if (isset($seen[$key])) {
                        $validator->errors()->add(
                            "items.{$index}.variety_id",
                            'Esta variedad con esos mismos largos ya está en la lista.',
                        );
                    }

                    $seen[$key] = true;
                }
            },
        ];
    }

    private function lastNeededByDate(): string
    {
        return now()->addDays((int) config('florea.needed_by_days'))->toDateString();
    }
}
