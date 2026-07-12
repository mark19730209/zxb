<?php

use App\Models\User;

it('renders the preserve scroll page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/preserve-scroll');

    $page->assertSee('Preserve Scroll')
        ->assertSee('Reload Options')
        ->assertSee('Scroll Region')
        ->assertNoJavaScriptErrors();
});
