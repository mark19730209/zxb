<?php

use App\Models\User;

it('renders the preserve state page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/preserve-state');

    $page->assertSee('Preserve State')
        ->assertSee('Local Component State')
        ->assertSee('Server Props')
        ->assertNoJavaScriptErrors();
});
