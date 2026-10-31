<?php

namespace App\Http\Requests;

use App\Enums\DeliveryResult;
use App\Enums\RejectedRange;
use App\Models\Deal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRatingRequest extends FormRequest
{
    public function deal(): Deal
    {
        $deal = $this->route('deal');

        abort_unless($deal instanceof Deal, 404);

        return $deal;
    }

    /**
     * La Policy va ANTES que las reglas.
     *
     * Si no, un extraño recibe un error de validación ("solo quien compra
     * reporta el rechazo") en vez de un 403, y de paso se entera de cómo
     * funciona el formulario de un trato que no es suyo.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('rate', $this->deal()) ?? false;
    }

    /**
     * Solo el comprador dice cuánto se rechazó: es quien recibió la flor y
     * la revisó. Su respuesta es la que se guarda en el trato.
     */
    private function isBuyer(): bool
    {
        $company = $this->user()?->company;

        return $company !== null && $this->deal()->isBuyer($company);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'delivered' => ['required', Rule::enum(DeliveryResult::class)],
            'on_time' => ['required', 'boolean'],
            'rejected_range' => [
                $this->isBuyer() ? 'required' : 'prohibited',
                Rule::enum(RejectedRange::class),
            ],
            'score' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:'.config('florea.notes_max')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'delivered.required' => 'Dinos si se entregó.',
            'on_time.required' => 'Dinos si llegó a tiempo.',
            'rejected_range.required' => 'Dinos cuánto se rechazó.',
            'rejected_range.prohibited' => 'Solo quien compra reporta el rechazo.',
            'score.required' => 'Pon las estrellas.',
            'score.between' => 'Las estrellas van de 1 a 5.',
        ];
    }
}
