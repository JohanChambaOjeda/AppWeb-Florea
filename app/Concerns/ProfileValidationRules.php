<?php

namespace App\Concerns;

use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'phone' => $this->phoneRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Reglas del celular. Se aplican al valor ya normalizado con normalizePhone().
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function phoneRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'regex:/^\+5939\d{8}$/',
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function phoneMessages(): array
    {
        return [
            'phone.regex' => 'Escribe un celular de Ecuador, por ejemplo 0991234567.',
            'phone.unique' => 'Este celular ya tiene una cuenta. Intenta ingresar.',
        ];
    }

    /**
     * Devuelve el celular en E.164 si es válido; si no, el valor original
     * (para que la regla regex muestre el error).
     */
    protected function normalizePhone(mixed $value): mixed
    {
        return is_string($value) ? (PhoneNumber::normalize($value) ?? $value) : $value;
    }
}
