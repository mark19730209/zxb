<?php

use App\Models\User;

it('renders the prop merging page with notifications', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/data-loading/prop-merging');

    $page->assertSee('Prop Merging')
        ->assertSee('Notifications')
        ->assertSee('Add Notification')
        ->assertSee('Prepend')
        ->assertSee('Log Activity')
        ->assertSee('matchOn')
        ->assertSee('Fetch Next Contact')
        ->assertNoJavaScriptErrors();
});
