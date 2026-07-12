<?php

use App\Models\User;

it('renders the precognition demo page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/precognition');

    $page->assertSee('Precognition')
        ->assertSee('Create Account')
        ->assertSee('Field Status')
        ->assertNoJavaScriptErrors();
});
