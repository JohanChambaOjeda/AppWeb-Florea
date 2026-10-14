<?php

use App\Models\Company;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('lista solo las empresas por verificar en la pestaña por defecto', function () {
    $pending = Company::factory()->create(['name' => 'Finca Pendiente']);
    Company::factory()->verified()->create();
    Company::factory()->create()->reject();
    User::factory()->create(['company_id' => $pending->id, 'phone' => '+593991234567']);

    $this->actingAs($this->admin)
        ->get(route('admin.companies.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/companies/index')
            ->has('companies.data', 1)
            ->where('companies.data.0.name', 'Finca Pendiente')
            ->where('companies.data.0.ownerPhone', '099 123 4567')
            ->where('pendingCount', 1));
});

test('la pestaña todas filtra por tipo y estado', function () {
    Company::factory()->grower()->create();
    Company::factory()->grower()->verified()->create();
    Company::factory()->postharvest()->verified()->create();
    Company::factory()->grower()->create()->reject();

    $this->actingAs($this->admin);

    $this->get(route('admin.companies.index', ['tab' => 'all']))
        ->assertInertia(fn ($page) => $page->has('companies.data', 4));

    $this->get(route('admin.companies.index', ['tab' => 'all', 'type' => 'grower']))
        ->assertInertia(fn ($page) => $page->has('companies.data', 3));

    $this->get(route('admin.companies.index', ['tab' => 'all', 'type' => 'grower', 'status' => 'verified']))
        ->assertInertia(fn ($page) => $page->has('companies.data', 1));

    $this->get(route('admin.companies.index', ['tab' => 'all', 'status' => 'rejected']))
        ->assertInertia(fn ($page) => $page
            ->has('companies.data', 1)
            ->where('companies.data.0.status', 'rejected'));
});

test('el admin verifica una empresa y esta ya puede entrar', function () {
    $owner = User::factory()->unverifiedCompany()->create();
    $company = $owner->company;

    $this->actingAs($owner)->get(route('dashboard'))->assertRedirect(route('account.pending'));

    $this->actingAs($this->admin)
        ->post(route('admin.companies.verify', $company))
        ->assertRedirect();

    expect($company->refresh())
        ->isVerified()->toBeTrue()
        ->verified_by->toBe($this->admin->id);

    $this->actingAs($owner->refresh())->get(route('dashboard'))->assertOk();
});

test('el admin rechaza una empresa con motivo y el dueño lo ve', function () {
    $owner = User::factory()->unverifiedCompany()->create();
    $company = $owner->company;

    $this->actingAs($this->admin)
        ->post(route('admin.companies.reject', $company), ['reason' => 'El RUC no corresponde.'])
        ->assertRedirect();

    expect($company->refresh())
        ->trashed()->toBeTrue()
        ->rejection_reason->toBe('El RUC no corresponde.');

    $this->actingAs($owner->refresh())->get(route('dashboard'))->assertRedirect(route('account.pending'));

    $this->get(route('account.pending'))
        ->assertInertia(fn ($page) => $page
            ->component('account/pending')
            ->where('rejected', true)
            ->where('rejectionReason', 'El RUC no corresponde.'));
});

test('el motivo de rechazo es opcional', function () {
    $company = Company::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.companies.reject', $company))
        ->assertSessionHasNoErrors();

    expect($company->refresh()->trashed())->toBeTrue();
});

test('no se rechaza una empresa ya verificada', function () {
    $company = Company::factory()->verified()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.companies.reject', $company))
        ->assertStatus(422);

    expect($company->refresh()->trashed())->toBeFalse();
});

test('un usuario normal no puede verificar empresas', function () {
    $company = Company::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('admin.companies.verify', $company))
        ->assertForbidden();

    expect($company->refresh()->isVerified())->toBeFalse();
});
