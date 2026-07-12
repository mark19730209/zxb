<?php

use App\Models\User;

it('renders the instant visits source page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/instant-visits');

    $page->assertSee('Instant Visits')
        ->assertSee('Basic Instant Visit')
        ->assertSee('With Placeholder Props')
        ->assertSee('Callback Props')
        ->assertSee('Link Component')
        ->assertSee('How It Works')
        ->assertNoJavaScriptErrors();
});

it('renders the instant visit target page directly', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/instant-visit-target?delay=0');

    $page->assertSee('Instant Visit Target')
        ->assertSee('Server Data')
        ->assertSee('Hello from the server!')
        ->assertNoJavaScriptErrors();
});
