<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\CompanyType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Registro de una empresa (poscosecha o productor) con su usuario dueño.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $input['phone'] = $this->normalizePhone($input['phone'] ?? null);

        $data = Validator::make($input, [
            'type' => ['required', Rule::enum(CompanyType::class)],
            'company_name' => ['required', 'string', 'max:255'],
            'tax_id' => ['required', 'regex:/^(\d{10}|\d{13})$/'],
            'zone_id' => ['required', Rule::exists('zones', 'id')->where('is_active', true)],
            'address_reference' => ['nullable', 'string', 'max:255'],
            'name' => $this->nameRules(),
            'phone' => $this->phoneRules(),
            'password' => ['required', 'string', Password::default()],
            // La ley ecuatoriana pide poder demostrar el consentimiento,
            // así que además de exigirlo se guarda la fecha.
            'terms' => ['accepted'],
        ], [
            ...$this->phoneMessages(),
            'tax_id.regex' => 'El RUC tiene 13 dígitos y la cédula 10. Escribe solo números.',
            'terms.accepted' => 'Para crear la cuenta hay que aceptar los términos y la política de privacidad.',
        ], [
            'type' => 'tipo de empresa',
            'company_name' => 'nombre de la finca o poscosecha',
            'tax_id' => 'RUC o cédula',
            'zone_id' => 'sector',
            'address_reference' => 'referencia de ubicación',
            'name' => 'tu nombre',
            'phone' => 'celular',
        ])->validate();

        return DB::transaction(function () use ($data) {
            $company = Company::create([
                'type' => $data['type'],
                'name' => $data['company_name'],
                'tax_id' => $data['tax_id'],
                'zone_id' => $data['zone_id'],
                'address_reference' => $data['address_reference'] ?? null,
            ]);

            return User::create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'role' => UserRole::Owner,
                'password' => $data['password'],
                'terms_accepted_at' => now(),
            ]);
        });
    }
}
