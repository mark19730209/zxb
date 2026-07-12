<?php

use App\Models\User;

it('renders the flash data page with server and client flash options', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/state/flash-data');

    $page->assertSee('Flash Data')
        ->assertSee('Server-Side Flash')
        ->assertSee('Client-Side Flash')
        ->assertSee('Current Flash State')
        ->assertSee('Event Log')
        ->assertNoJavaScriptErrors();
});
