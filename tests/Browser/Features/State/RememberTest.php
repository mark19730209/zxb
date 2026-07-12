<?php

use App\Models\User;

it('renders the remember page with form state and counter', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/state/remember');

    $page->assertSee('Remember')
        ->assertSee('useRemember()')
        ->assertSee('Remembered Form State')
        ->assertSee('Remembered Counter')
        ->assertNoJavaScriptErrors();
});
