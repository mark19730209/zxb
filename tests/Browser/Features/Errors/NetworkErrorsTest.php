<?php

use App\Models\User;

it('renders the network errors page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/errors/network-errors');

    $page->assertSee('Network Errors')
        ->assertSee('When Network Errors Occur')
        ->assertSee('Error Interception')
        ->assertSee('API Reference')
        ->assertNoJavaScriptErrors();
});
