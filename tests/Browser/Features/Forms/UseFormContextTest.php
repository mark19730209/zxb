<?php

use App\Models\User;

it('renders the useFormContext demo page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/use-form-context');

    $page->assertSee('useFormContext')
        ->assertSee('Parent Form')
        ->assertSee('How It Works')
        ->assertNoJavaScriptErrors();
});

it('shows child component fields using form context', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/use-form-context');

    $page->assertSee('Name')
        ->assertSee('Email')
        ->assertSee('Bio')
        ->assertSee('Parent Slot Props')
        ->assertNoJavaScriptErrors();
});

it('submits the form successfully via form context child fields', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/use-form-context');

    $page->fill('#ctx-name', 'John Doe')
        ->fill('#ctx-email', 'john@example.com')
        ->fill('#ctx-bio', 'Test bio')
        ->submit('form')
        ->waitForText('Form submitted successfully')
        ->assertNoJavaScriptErrors();
});
