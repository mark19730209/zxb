<?php

use App\Models\User;

it('renders the instant visit target page with server data', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/instant-visit-target?delay=0');

    $page->assertSee('Instant Visit Target')
        ->assertSee('Server Data')
        ->assertSee('Hello from the server!')
        ->assertNoJavaScriptErrors();
});

it('shows items list from the server', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/instant-visit-target?delay=0');

    $page->assertSee('Items List')
        ->assertSee('Server Item A')
        ->assertNoJavaScriptErrors();
});
