<?php

use App\Models\User;

it('renders the http exceptions page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/errors/http-exceptions');

    $page->assertSee('HTTP Exceptions')
        ->assertSee('Server-Handled Errors')
        ->assertSee('Client-Side Interception')
        ->assertSee('API Reference')
        ->assertNoJavaScriptErrors();
});
