<?php

use App\Models\User;

it('renders the dotted keys demo page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/dotted-keys');

    $page->assertSee('Dotted Keys')
        ->assertSee('Nested Form Fields')
        ->assertSee('Parsed Request Data')
        ->assertNoJavaScriptErrors();
});

it('shows nested field groups', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/dotted-keys');

    $page->assertSee('User')
        ->assertSee('Address')
        ->assertSee('Tags')
        ->assertNoJavaScriptErrors();
});

it('submits nested form data and shows parsed structure', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/dotted-keys');

    $page->fill('#dk-user-name', 'John Doe')
        ->fill('#dk-user-email', 'john@example.com')
        ->fill('#dk-address-street', '123 Main St')
        ->fill('#dk-address-city', 'Springfield')
        ->submit('form')
        ->waitForText('Form submitted successfully')
        ->assertSee('John Doe')
        ->assertNoJavaScriptErrors();
});
