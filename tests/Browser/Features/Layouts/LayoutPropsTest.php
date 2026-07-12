<?php

use App\Models\User;

it('renders the layout props page with setLayoutProps demo', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/layouts/layout-props');

    $page->assertSee('Layout Props')
        ->assertSee('setLayoutProps()')
        ->assertSee('API Reference')
        ->assertSee('resetLayoutProps()')
        ->assertNoJavaScriptErrors();
});

it('shows a subtitle banner when applying layout props', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/layouts/layout-props');

    $page->type('#subtitle', 'Welcome back!')
        ->click('Apply')
        ->waitForText('Welcome back!')
        ->assertSeeIn('[data-test="layout-subtitle"]', 'Welcome back!')
        ->assertNoJavaScriptErrors();
});

it('resets the subtitle banner', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/layouts/layout-props');

    $page->type('#subtitle', 'Welcome back!')
        ->click('Apply')
        ->waitForText('Welcome back!')
        ->assertSeeIn('[data-test="layout-subtitle"]', 'Welcome back!')
        ->click('Reset')
        ->assertSourceMissing('data-test="layout-subtitle"')
        ->assertNoJavaScriptErrors();
});
