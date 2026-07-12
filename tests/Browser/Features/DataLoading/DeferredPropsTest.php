<?php

use App\Models\User;

it('renders the deferred props page with instant and deferred data', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/data-loading/deferred-props');

    $page->assertSee('Deferred Props')
        ->assertSee('Instant Prop')
        ->assertSee('Loaded instantly')
        ->assertSee('Deferred Stats')
        ->assertSee('Heavy Data')
        ->assertSee('Reloading Slot')
        ->assertNoJavaScriptErrors();
});
