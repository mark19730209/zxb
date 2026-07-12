<?php

use App\Models\User;

it('renders the redirects page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/redirects');

    $page->assertSee('Redirects')
        ->assertSee('redirect()->back()')
        ->assertSee('to_route()')
        ->assertSee('Inertia::location()')
        ->assertSee('303 Redirect Behavior')
        ->assertNoJavaScriptErrors();
});

it('shows flash message after redirect back', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/redirects');

    $page->click('Submit and redirect back');

    sleep(2);

    $page->assertSee('Redirected back via redirect()->back()')
        ->assertNoJavaScriptErrors();
});
