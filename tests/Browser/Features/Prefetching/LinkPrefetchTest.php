<?php

use App\Models\User;

it('renders the link prefetch page with prefetch modes', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/prefetching/link-prefetch');

    $page->assertSee('Link Prefetch')
        ->assertSee('Hover Prefetch')
        ->assertSee('Click Prefetch')
        ->assertSee('Mount Prefetch')
        ->assertSee('cacheFor')
        ->assertNoJavaScriptErrors();
});
