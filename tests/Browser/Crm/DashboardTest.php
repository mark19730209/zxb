<?php

use App\Models\Contact;
use App\Models\Note;
use App\Models\Organization;
use App\Models\User;

it('displays dashboard with stats cards', function () {
    $user = User::factory()->create();
    Contact::factory()->count(5)->create();
    Organization::factory()->count(3)->create();

    $this->actingAs($user);

    $page = visit('/dashboard');

    $page->assertSee('Total Contacts')
        ->assertSee('Organizations')
        ->assertSee('Notes This Week')
        ->assertNoJavaScriptErrors();
});

it('displays recent activity on dashboard', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    Note::factory()->create([
        'contact_id' => $contact->id,
        'user_id' => $user->id,
        'body' => 'Dashboard test note',
    ]);

    $this->actingAs($user);

    $page = visit('/dashboard');

    $page->assertSee('Recent Activity')
        ->assertSee('Dashboard test note')
        ->assertNoJavaScriptErrors();
});
