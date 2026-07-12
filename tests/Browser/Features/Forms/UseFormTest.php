<?php

use App\Models\User;

it('renders the useForm demo page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/use-form');

    $page->assertSee('useForm')
        ->assertSee('Reactive State')
        ->assertNoJavaScriptErrors();
});

it('shows validation errors on empty submit', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/use-form');

    $page->click('Submit');

    sleep(2);

    $page->assertSee('The name field is required')
        ->assertNoJavaScriptErrors();
});

it('submits the form successfully', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/use-form');

    $page->fill('name', 'John Doe')
        ->fill('email', 'john@example.com');

    $page->click('Submit');

    sleep(2);

    $page->assertSee('Form submitted successfully')
        ->assertNoJavaScriptErrors();
});
