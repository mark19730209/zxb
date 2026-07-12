<?php

use App\Models\User;

it('renders the infinite scroll page with contacts', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/data-loading/infinite-scroll');

    $page->assertSee('Infinite Scroll')
        ->assertSee('Scroll Mode')
        ->assertSee('Auto (default)')
        ->assertSee('Manual')
        ->assertNoJavaScriptErrors();
});
