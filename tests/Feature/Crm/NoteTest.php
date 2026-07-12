<?php

use App\Models\Contact;
use App\Models\Note;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// Store

it('creates a note for a contact', function () {
    $contact = Contact::factory()->create();

    $this->post(route('contacts.notes.store', $contact), [
        'body' => 'This is a test note.',
    ])->assertRedirect();

    $this->assertDatabaseHas('notes', [
        'body' => 'This is a test note.',
        'contact_id' => $contact->id,
        'user_id' => $this->user->id,
    ]);
});

it('validates note body is required', function () {
    $contact = Contact::factory()->create();

    $this->post(route('contacts.notes.store', $contact), [
        'body' => '',
    ])->assertSessionHasErrors(['body']);
});

it('associates note with authenticated user', function () {
    $contact = Contact::factory()->create();

    $this->post(route('contacts.notes.store', $contact), [
        'body' => 'User note.',
    ]);

    $note = Note::first();
    expect($note->user_id)->toBe($this->user->id);
});

// Auth

it('requires authentication', function () {
    auth()->logout();
    $contact = Contact::factory()->create();

    $this->post(route('contacts.notes.store', $contact))->assertRedirect(route('login'));
});
