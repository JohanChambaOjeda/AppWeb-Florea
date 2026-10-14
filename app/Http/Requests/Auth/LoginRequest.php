<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

/**
 * Normaliza el celular (0991234567, 991234567, +593991234567) a E.164
 * antes de que Fortify busque al usuario.
 */
class LoginRequest extends FortifyLoginRequest
{
    protected function prepareForValidation(): void
    {
        $phone = PhoneNumber::normalize($this->input('phone'));

        if ($phone !== null) {
            $this->merge(['phone' => $phone]);
        }
    }

    public function attributes(): array
    {
        return ['phone' => 'celular'];
    }
}
