<?php

use App\Models\User;

it('renders the progress bar page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/events/progress');

    $page->assertSee('Progress Bar')
        ->assertSee('Trigger Progress')
        ->assertSee('Configuration')
        ->assertSee('Per-Visit Control')
        ->assertNoJavaScriptErrors();
});
