<?php

use App\Models\User;

it('renders the partial reloads page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/data-loading/partial-reloads');

    $page->assertSee('Partial Reloads')
        ->assertSee('Current Props')
        ->assertSee('Reload Controls')
        ->assertSee('Alice')
        ->assertSee('Bob')
        ->assertSee('Charlie')
        ->assertNoJavaScriptErrors();
});
