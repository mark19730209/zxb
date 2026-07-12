<?php

use App\Models\User;

it('renders the persistent layouts page with layout state', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/layouts/persistent-layouts');

    $page->assertSee('Persistent Layouts')
        ->assertSee('Stopwatch')
        ->assertSee('Counter')
        ->assertSee('Page 1 State')
        ->assertSee('How It Works')
        ->assertNoJavaScriptErrors();
});

it('renders page two of persistent layouts', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/layouts/persistent-layouts/page-2');

    $page->assertSee('Persistent Layouts')
        ->assertSee('Stopwatch')
        ->assertSee('Page 2 State')
        ->assertSee('What Persists')
        ->assertNoJavaScriptErrors();
});
