<?php

use App\Models\User;

it('renders the async requests page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/async-requests');

    $page->assertSee('Async Requests')
        ->assertSee('Synchronous (Default)')
        ->assertSee('Async (Concurrent)')
        ->assertSee('Cancel Token')
        ->assertNoJavaScriptErrors();
});
