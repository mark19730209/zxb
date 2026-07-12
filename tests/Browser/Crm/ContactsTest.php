<?php

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;

it('displays the contacts list', function () {
    $user = User::factory()->create();
    Contact::factory()->create(['first_name' => 'Alice', 'last_name' => 'Wonder']);

    $this->actingAs($user);

    $page = visit('/contacts');

    $page->assertSee('Contacts')
        ->assertSee('Alice Wonder')
        ->assertSee('Add Contact')
        ->assertNoJavaScriptErrors();
});

it('can navigate to contact detail', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create(['first_name' => 'Alice', 'last_name' => 'Wonder']);

    $this->actingAs($user);

    $page = visit('/contacts');

    $page->click('Alice Wonder')
        ->assertPathIs("/contacts/{$contact->id}")
        ->assertSee('Alice Wonder')
        ->assertSee('Contact Information')
        ->assertNoJavaScriptErrors();
});

it('displays the create contact form', function () {
    $user = User::factory()->create();
    Organization::factory()->create(['name' => 'Acme Corp']);

    $this->actingAs($user);

    $page = visit('/contacts/create');

    $page->assertSee('Create Contact')
        ->assertSee('Contact Information')
        ->assertSee('First Name')
        ->assertSee('Last Name')
        ->assertSee('Organization')
        ->assertNoJavaScriptErrors();
});

it('shows validation errors on create', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $page = visit('/contacts/create');

    $page->press('Create Contact')
        ->waitForText('first name')
        ->assertNoJavaScriptErrors();
});

it('can create a new contact', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $page = visit('/contacts/create');

    $page->type('#first_name', 'New')
        ->type('#last_name', 'Person')
        ->type('#email', 'new@example.com')
        ->submit()
        ->waitForText('New Person')
        ->assertSee('Notes')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('contacts', [
        'first_name' => 'New',
        'last_name' => 'Person',
        'email' => 'new@example.com',
    ]);
});

it('can view contact detail with notes section', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'first_name' => 'Alice',
        'last_name' => 'Wonder',
        'email' => 'alice@example.com',
    ]);

    $this->actingAs($user);

    $page = visit("/contacts/{$contact->id}");

    $page->assertSee('Alice Wonder')
        ->assertSee('alice@example.com')
        ->assertSee('Notes')
        ->assertSee('Edit')
        ->assertSee('Delete')
        ->assertNoJavaScriptErrors();
});

it('can navigate to edit contact', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'first_name' => 'Alice',
        'last_name' => 'Wonder',
    ]);

    $this->actingAs($user);

    $page = visit("/contacts/{$contact->id}");

    $page->click('Edit')
        ->assertPathIs("/contacts/{$contact->id}/edit")
        ->assertSee('Edit Contact')
        ->assertNoJavaScriptErrors();
});

it('displays edit contact form with existing data', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'first_name' => 'Alice',
        'last_name' => 'Wonder',
        'email' => 'alice@example.com',
    ]);

    $this->actingAs($user);

    $page = visit("/contacts/{$contact->id}/edit");

    $page->assertSee('Edit Contact')
        ->assertValue('#first_name', 'Alice')
        ->assertValue('#last_name', 'Wonder')
        ->assertValue('#email', 'alice@example.com')
        ->assertNoJavaScriptErrors();
});
