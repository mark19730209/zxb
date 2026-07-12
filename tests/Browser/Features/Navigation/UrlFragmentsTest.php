<?php

use App\Models\User;

it('renders the url fragments page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/url-fragments');

    $page->assertSee('URL Fragments')
        ->assertSee('Hash Fragment Redirect')
        ->assertSee('Preserve Fragment')
        ->assertSee('Key Differences')
        ->assertNoJavaScriptErrors();
});

it('renders the preserve fragment target page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/url-fragments/preserve-target');

    $page->assertSee('URL Fragments')
        ->assertSee('Redirected via preserveFragment redirect')
        ->assertNoJavaScriptErrors();
});
