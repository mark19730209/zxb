<?php

use App\Models\User;

it('renders the visit callbacks page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/events/visit-callbacks');

    $page->assertSee('Visit Callbacks')
        ->assertSee('Trigger Callbacks')
        ->assertSee('Available Callbacks')
        ->assertNoJavaScriptErrors();
});
