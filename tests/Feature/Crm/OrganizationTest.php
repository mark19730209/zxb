<?php

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// Index

it('displays the organizations index', function () {
    Organization::factory()->count(3)->create();

    $this->get(route('organizations.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Organizations/Index')
            ->has('organizations.data', 3)
        );
});

it('filters organizations by search', function () {
    Organization::factory()->create(['name' => 'Acme Corp']);
    Organization::factory()->create(['name' => 'Globex Inc']);

    $this->get(route('organizations.index', ['search' => 'Acme']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('organizations.data', 1)
            ->where('organizations.data.0.name', 'Acme Corp')
        );
});

it('paginates organizations', function () {
    Organization::factory()->count(25)->create();

    $this->get(route('organizations.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('organizations.data', 20)
        );
});

it('includes contact count', function () {
    $org = Organization::factory()->create();
    Contact::factory()->count(5)->create(['organization_id' => $org->id]);

    $this->get(route('organizations.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('organizations.data.0.contacts_count', 5)
        );
});

// Show

it('displays an organization', function () {
    $org = Organization::factory()->create();
    Contact::factory()->count(3)->create(['organization_id' => $org->id]);

    $this->get(route('organizations.show', $org))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Organizations/Show')
            ->has('organization')
            ->where('organization.id', $org->id)
            ->has('contacts.data', 3)
        );
});

// Update

it('updates an organization', function () {
    $org = Organization::factory()->create(['name' => 'Old Name']);

    $this->put(route('organizations.update', $org), [
        'name' => 'New Name',
    ])->assertRedirect();

    expect($org->fresh()->name)->toBe('New Name');
});

it('validates organization name on update', function () {
    $org = Organization::factory()->create();

    $this->put(route('organizations.update', $org), [
        'name' => '',
    ])->assertSessionHasErrors(['name']);
});

// Auth

it('requires authentication for all organization routes', function () {
    auth()->logout();
    $org = Organization::factory()->create();

    $this->get(route('organizations.index'))->assertRedirect(route('login'));
    $this->get(route('organizations.show', $org))->assertRedirect(route('login'));
    $this->put(route('organizations.update', $org))->assertRedirect(route('login'));
});
