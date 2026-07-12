<?php

use App\Models\User;

it('renders the view transitions page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/navigation/view-transitions');

    $page->assertSee('View Transitions')
        ->assertSee('With View Transitions')
        ->assertSee('Without View Transitions')
        ->assertNoJavaScriptErrors();
});
