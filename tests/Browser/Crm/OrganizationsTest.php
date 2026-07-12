<?php

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;

it('displays the organizations list', function () {
    $user = User::factory()->create();
    Organization::factory()->create(['name' => 'Acme Corp']);

    $this->actingAs($user);

    $page = visit('/organizations');

    $page->assertSee('Organizations')
        ->assertSee('Acme Corp')
        ->assertNoJavaScriptErrors();
});

it('shows contact count badge', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create(['name' => 'Acme Corp']);
    Contact::factory()->count(3)->create(['organization_id' => $org->id]);

    $this->actingAs($user);

    $page = visit('/organizations');

    $page->assertSee('Acme Corp')
        ->assertSee('3 contacts')
        ->assertNoJavaScriptErrors();
});

it('can view organization detail with contacts', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create(['name' => 'Acme Corp']);
    Contact::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'organization_id' => $org->id,
    ]);

    $this->actingAs($user);

    $page = visit("/organizations/{$org->id}");

    $page->assertSee('Acme Corp')
        ->assertSee('Members')
        ->assertSee('John Doe')
        ->assertNoJavaScriptErrors();
});

it('can navigate from org list to detail', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create(['name' => 'Acme Corp']);

    $this->actingAs($user);

    $page = visit('/organizations');

    $page->click('Acme Corp')
        ->assertPathIs("/organizations/{$org->id}")
        ->assertSee('Acme Corp')
        ->assertSee('Organization Name')
        ->assertNoJavaScriptErrors();
});

it('displays inline edit form on organization detail', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create(['name' => 'Acme Corp']);

    $this->actingAs($user);

    $page = visit("/organizations/{$org->id}");

    $page->assertSee('Organization Name')
        ->assertValue('#name', 'Acme Corp')
        ->assertButtonDisabled('Update')
        ->assertNoJavaScriptErrors();
});
