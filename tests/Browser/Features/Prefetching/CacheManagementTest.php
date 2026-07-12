<?php

use App\Models\User;

it('renders the cache management page with flush actions', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/prefetching/cache-management');

    $page->assertSee('Cache Management')
        ->assertSee('Cache Tags')
        ->assertSee('Flush All Cache')
        ->assertNoJavaScriptErrors();
});
