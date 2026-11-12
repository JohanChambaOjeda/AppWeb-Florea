<?php

namespace App\Http\Requests;

use App\Enums\DeliveryResult;
use App\Enums\PaidAsAgreed;
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
     * Cada uno responde por lo que vivió, y nada más.
     *
     * La poscosecha recibió la flor: si llegó, si llegó a tiempo y cuánto
     * tuvo que rechazar. El productor cobró: si le pagaron como acordaron y
     * si le respetaron lo hablado al recibir. Las estrellas y el comentario
     * son de los dos.
     *
     * Las preguntas del otro van `prohibited`, no ignoradas: si llegan, es
     * que alguien está mandando el formulario a mano.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $buys = $this->isBuyer();

        return [
            // Preguntas de quien compra (la poscosecha).
            'delivered' => [$buys ? 'required' : 'prohibited', Rule::enum(DeliveryResult::class)],
            'on_time' => [$buys ? 'required' : 'prohibited', 'boolean'],
            'rejected_range' => [$buys ? 'required' : 'prohibited', Rule::enum(RejectedRange::class)],

            // Preguntas de quien vende (el productor).
            'paid_as_agreed' => [$buys ? 'prohibited' : 'required', Rule::enum(PaidAsAgreed::class)],
            'honored_agreement' => [$buys ? 'prohibited' : 'required', 'boolean'],

            // De los dos.
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
            'delivered.prohibited' => 'Solo quien compra reporta la entrega.',
            'on_time.required' => 'Dinos si llegó a tiempo.',
            'on_time.prohibited' => 'Solo quien compra reporta la puntualidad.',
            'rejected_range.required' => 'Dinos cuánto se rechazó.',
            'rejected_range.prohibited' => 'Solo quien compra reporta el rechazo.',
            'paid_as_agreed.required' => 'Dinos si te pagaron como acordaron.',
            'paid_as_agreed.prohibited' => 'Solo quien vende reporta el pago.',
            'honored_agreement.required' => 'Dinos si respetaron lo acordado.',
            'honored_agreement.prohibited' => 'Solo quien vende reporta esto.',
            'score.required' => 'Pon las estrellas.',
            'score.between' => 'Las estrellas van de 1 a 5.',
        ];
    }
}
