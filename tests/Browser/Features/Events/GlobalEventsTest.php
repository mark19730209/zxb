<?php

use App\Models\User;

it('renders the global events page with event types', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/events/global-events');

    $page->assertSee('Global Events')
        ->assertSee('Trigger Events')
        ->assertSee('Available Events')
        ->assertSee('Event Log')
        ->assertNoJavaScriptErrors();
});
