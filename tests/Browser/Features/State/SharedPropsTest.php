<?php

use App\Models\User;

it('renders the shared props page with usePage data', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/state/shared-props');

    $page->assertSee('Shared Props')
        ->assertSee('Current Shared Props')
        ->assertSee('usePage() Data')
        ->assertSee('Server Configuration')
        ->assertNoJavaScriptErrors();
});
