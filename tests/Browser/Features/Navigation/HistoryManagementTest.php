<?php

use App\Models\User;

it('renders the history management page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/history-management');

    $page->assertSee('History Management')
        ->assertSee('replace: true')
        ->assertSee('preserveUrl: true')
        ->assertNoJavaScriptErrors();
});
