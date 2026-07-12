<?php

use App\Models\User;

it('renders the when visible page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/data-loading/when-visible');

    $page->assertSee('When Visible')
        ->assertSee('How It Works')
        ->assertSee('Scroll down to trigger lazy loading')
        ->assertNoJavaScriptErrors();
});
