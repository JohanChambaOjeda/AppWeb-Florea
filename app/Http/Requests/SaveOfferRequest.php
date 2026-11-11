<?php

namespace App\Http\Requests;

use App\Enums\Delivery;
use App\Enums\Unit;
use App\Models\Listing;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Regla 4: lo que se exige depende del tipo de publicación, no del tipo de
 * empresa. En un "Busco" quien oferta vende, así que foto y precio son
 * obligatorios. En un "Disponible" quien oferta compra: sin foto y con
 * precio opcional.
 */
class SaveOfferRequest extends FormRequest
{
    public const MAX_PHOTOS = 3;

    /**
     * La Policy va ANTES que la validación.
     *
     * Sin esto, quien no puede ofertar —el tipo que no le toca por la regla
     * 8, su propia publicación, una ya cerrada— recibe "Pon el precio por
     * tallo" en vez de un 403, y de paso se entera de cómo es el formulario
     * de una publicación en la que no pinta nada.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('offer', $this->listing()) ?? false;
    }

    public function listing(): Listing
    {
        $listing = $this->route('listing');

        abort_unless($listing instanceof Listing, 404);

        return $listing;
    }

    private function offererSells(): bool
    {
        return $this->listing()->kind->offererSells();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $min = (int) config('florea.custom_length.min');
        $max = (int) config('florea.custom_length.max');
        $itemIds = $this->listing()->items()->pluck('id')->all();

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.listing_item_id' => ['required', 'integer', 'distinct', Rule::in($itemIds)],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'items.*.unit' => ['required', Rule::enum(Unit::class)],
            'items.*.length' => ['required', 'integer', "between:{$min},{$max}"],

            'price_per_stem' => [
                $this->offererSells() ? 'required' : 'nullable',
                'numeric', 'min:0.01', 'max:9999.99', 'decimal:0,2',
            ],

            'delivery' => ['required', Rule::enum(Delivery::class)],
            'notes' => ['nullable', 'string', 'max:'.config('florea.notes_max')],

            // Fotos: las que ya estaban y se conservan, más las nuevas.
            'keep_photos' => ['array', 'max:'.self::MAX_PHOTOS],
            'keep_photos.*' => ['integer'],
            'photos' => [$this->offererSells() ? 'array' : 'prohibited', 'max:'.self::MAX_PHOTOS],
            'photos.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:12288'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Marca al menos una línea de la publicación.',
            'items.min' => 'Marca al menos una línea de la publicación.',
            'items.*.listing_item_id.in' => 'Esa línea no es de esta publicación.',
            'items.*.quantity.required' => 'Pon la cantidad.',
            'items.*.quantity.min' => 'La cantidad debe ser mayor que cero.',
            'items.*.unit.required' => 'Elige la unidad.',
            'items.*.length.required' => 'Elige el largo que ofreces.',
            'price_per_stem.required' => 'Pon el precio por tallo.',
            'price_per_stem.decimal' => 'El precio lleva máximo 2 decimales. Por ejemplo 0.45.',
            'price_per_stem.min' => 'El precio debe ser mayor que cero.',
            'delivery.required' => 'Elige cómo se entrega.',
            'photos.prohibited' => 'En un "Disponible" no se manda foto.',
            'photos.*.image' => 'Sube una foto (JPG, PNG o WEBP).',
            'photos.*.mimes' => 'La foto debe ser JPG, PNG o WEBP.',
            'photos.*.max' => 'Cada foto puede pesar hasta 12 MB.',
        ];
    }

    /**
     * La foto del día es obligatoria para quien vende, entre 1 y 3. Se cuenta
     * el total: las que conserva más las que sube ahora.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->offererSells() || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $total = count((array) $this->input('keep_photos', []))
                    + count((array) $this->file('photos', []));

                if ($total < 1) {
                    $validator->errors()->add('photos', 'Sube al menos una foto de la flor de hoy.');
                }

                if ($total > self::MAX_PHOTOS) {
                    $validator->errors()->add('photos', 'Puedes mandar hasta 3 fotos.');
                }
            },
        ];
    }
}
