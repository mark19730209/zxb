<?php

use App\Models\User;

it('renders the once props page with static and dynamic data', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/data-loading/once-props');

    $page->assertSee('Once Props')
        ->assertSee('Navigate Between Pages')
        ->assertSee('Page 1')
        ->assertSee('Page 2')
        ->assertSee('Once Prop')
        ->assertSee('Dynamic Prop')
        ->assertSee('Force Refresh Once Prop')
        ->assertNoJavaScriptErrors();
});
