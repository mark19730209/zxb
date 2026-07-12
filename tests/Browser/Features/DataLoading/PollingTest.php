<?php

use App\Models\User;

it('renders the polling page with live data', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/data-loading/polling');

    $page->assertSee('Polling')
        ->assertSee('Polling Controls')
        ->assertSee('Live Data')
        ->assertSee('Start Polling')
        ->assertSee('Server Time')
        ->assertNoJavaScriptErrors();
});
