<?php

use App\Models\User;

it('renders the validation demo page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/validation');

    $page->assertSee('Validation')
        ->assertSee('Server-Side Validation')
        ->assertSee('Error Bags')
        ->assertNoJavaScriptErrors();
});

it('shows validation errors for the primary form', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/validation');

    $page->click('Submit');

    sleep(2);

    $page->assertSee('Please enter your full name')
        ->assertNoJavaScriptErrors();
});
