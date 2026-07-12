<?php

use App\Models\Contact;
use App\Models\User;

it('renders the optimistic updates demo page', function () {
    $this->actingAs(User::factory()->create());
    Contact::factory(5)->create();

    $page = visit('/features/forms/optimistic-updates');

    $page->assertSee('Optimistic Updates')
        ->assertSee('Event Log')
        ->assertNoJavaScriptErrors();
});

it('shows contacts list', function () {
    $this->actingAs(User::factory()->create());
    $contact = Contact::factory()->create(['first_name' => 'Alice', 'last_name' => 'Test']);

    $page = visit('/features/forms/optimistic-updates');

    $page->assertSee('Alice Test')
        ->assertNoJavaScriptErrors();
});
