<?php

use App\Models\User;

it('renders the optional props demo page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/data-loading/optional-props');

    $page->assertSee('Optional Props')
        ->assertSee('Regular Prop')
        ->assertSee('Optional Prop')
        ->assertSee('Deferred Prop')
        ->assertNoJavaScriptErrors();
});

it('shows regular prop as loaded and optional prop as not loaded on initial visit', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/data-loading/optional-props');

    $page->assertSee('This prop is always included')
        ->assertSee('Not loaded')
        ->assertNoJavaScriptErrors();
});

it('shows deferred prop after automatic loading', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/data-loading/optional-props');

    $page->waitForText('Total Contacts', 5)
        ->assertNoJavaScriptErrors();
});
