<?php

use App\Models\User;

it('renders the manual prefetch page with composable and router APIs', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/prefetching/manual-prefetch');

    $page->assertSee('Manual Prefetch')
        ->assertSee('router.prefetch()')
        ->assertNoJavaScriptErrors();
});
