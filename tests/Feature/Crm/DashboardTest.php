<?php

use App\Models\Contact;
use App\Models\Note;
use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('renders the dashboard page', function () {
    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Crm/Dashboard'));
});

it('includes recent activity', function () {
    $contact = Contact::factory()->create();
    Note::factory()->count(3)->create([
        'contact_id' => $contact->id,
        'user_id' => $this->user->id,
    ]);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Crm/Dashboard')
            ->has('recentActivity', 3)
        );
});

it('limits recent activity to 10 items', function () {
    $contact = Contact::factory()->create();
    Note::factory()->count(15)->create([
        'contact_id' => $contact->id,
        'user_id' => $this->user->id,
    ]);

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('recentActivity', 10)
        );
});

it('defers stats props', function () {
    Contact::factory()->count(5)->create();
    Organization::factory()->count(3)->create();

    $this->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Crm/Dashboard')
        );
});
