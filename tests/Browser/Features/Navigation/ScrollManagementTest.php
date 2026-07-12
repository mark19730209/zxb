<?php

use App\Models\User;

it('renders the scroll management page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/scroll-management');

    $page->assertSee('Scroll Management')
        ->assertSee('Scroll Behavior')
        ->assertSee('Scroll Regions')
        ->assertNoJavaScriptErrors();
});

it('shows the scrollable item list', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/scroll-management');

    $page->assertSee('Item #1')
        ->assertSee('Item #2')
        ->assertNoJavaScriptErrors();
});
