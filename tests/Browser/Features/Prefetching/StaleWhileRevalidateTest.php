<?php

use App\Models\User;

it('renders the stale while revalidate page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/prefetching/stale-while-revalidate');

    $page->assertSee('Stale While Revalidate')
        ->assertSee('How SWR Works')
        ->assertNoJavaScriptErrors();
});
