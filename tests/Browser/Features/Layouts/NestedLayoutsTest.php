<?php

use App\Models\User;

it('renders the nested layouts page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/layouts/nested-layouts');

    $page->assertSee('Nested Layouts')
        ->assertSee('How Nesting Works')
        ->assertSee('Nesting Visualization')
        ->assertNoJavaScriptErrors();
});
