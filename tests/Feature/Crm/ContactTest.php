<?php

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// Index

it('displays the contacts index', function () {
    Contact::factory()->count(3)->create();

    $this->get(route('contacts.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Contacts/Index')
            ->has('contacts.data', 3)
        );
});

it('filters contacts by search', function () {
    Contact::factory()->create(['first_name' => 'Alice', 'last_name' => 'Smith']);
    Contact::factory()->create(['first_name' => 'Bob', 'last_name' => 'Jones']);

    $this->get(route('contacts.index', ['search' => 'Alice']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('contacts.data', 1)
            ->where('contacts.data.0.first_name', 'Alice')
        );
});

it('filters contacts by favorite', function () {
    Contact::factory()->create(['is_favorite' => true]);
    Contact::factory()->count(2)->create(['is_favorite' => false]);

    $this->get(route('contacts.index', ['favorite' => '1']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('contacts.data', 1)
        );
});

it('returns paginated contacts with cursor', function () {
    Contact::factory()->count(20)->create();

    $this->get(route('contacts.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('contacts.data', 15)
        );
});

// Create

it('displays the create contact form', function () {
    Organization::factory()->count(2)->create();

    $this->get(route('contacts.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Contacts/Create')
            ->has('organizations', 2)
        );
});

// Store

it('stores a new contact', function () {
    $organization = Organization::factory()->create();

    $this->post(route('contacts.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '555-1234',
        'organization_id' => $organization->id,
    ])->assertRedirect();

    $this->assertDatabaseHas('contacts', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'organization_id' => $organization->id,
    ]);
});

it('validates required fields when creating', function () {
    $this->post(route('contacts.store'), [])
        ->assertSessionHasErrors(['first_name', 'last_name']);
});

it('validates email format', function () {
    $this->post(route('contacts.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'not-an-email',
    ])->assertSessionHasErrors(['email']);
});

// Show

it('displays a contact', function () {
    $contact = Contact::factory()->create();

    $this->get(route('contacts.show', $contact))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Contacts/Show')
            ->has('contact')
            ->where('contact.id', $contact->id)
        );
});

// Edit

it('displays the edit contact form', function () {
    $contact = Contact::factory()->create();

    $this->get(route('contacts.edit', $contact))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Contacts/Edit')
            ->has('contact')
            ->has('organizations')
        );
});

// Update

it('updates a contact', function () {
    $contact = Contact::factory()->create();

    $this->put(route('contacts.update', $contact), [
        'first_name' => 'Updated',
        'last_name' => 'Name',
        'email' => 'updated@example.com',
    ])->assertRedirect();

    expect($contact->fresh())
        ->first_name->toBe('Updated')
        ->last_name->toBe('Name')
        ->email->toBe('updated@example.com');
});

// Destroy

it('deletes a contact', function () {
    $contact = Contact::factory()->create();

    $this->delete(route('contacts.destroy', $contact))
        ->assertRedirect(route('contacts.index'));

    $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
});

// Favorite

it('toggles a contact favorite status', function () {
    $contact = Contact::factory()->create(['is_favorite' => false]);

    $this->post(route('contacts.favorite', $contact))
        ->assertRedirect();

    expect($contact->fresh()->is_favorite)->toBeTrue();

    $this->post(route('contacts.favorite', $contact))
        ->assertRedirect();

    expect($contact->fresh()->is_favorite)->toBeFalse();
});

// Auth

it('requires authentication for all contact routes', function () {
    auth()->logout();
    $contact = Contact::factory()->create();

    $this->get(route('contacts.index'))->assertRedirect(route('login'));
    $this->get(route('contacts.create'))->assertRedirect(route('login'));
    $this->post(route('contacts.store'))->assertRedirect(route('login'));
    $this->get(route('contacts.show', $contact))->assertRedirect(route('login'));
    $this->get(route('contacts.edit', $contact))->assertRedirect(route('login'));
    $this->put(route('contacts.update', $contact))->assertRedirect(route('login'));
    $this->delete(route('contacts.destroy', $contact))->assertRedirect(route('login'));
});
