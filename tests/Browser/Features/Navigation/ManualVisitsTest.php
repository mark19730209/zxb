<?php

use App\Models\User;

it('renders the manual visits page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/manual-visits');

    $page->assertSee('Client-Side Visits')
        ->assertSee('URL Replacement')
        ->assertSee('Prop Replacement')
        ->assertSee('Current State')
        ->assertNoJavaScriptErrors();
});
