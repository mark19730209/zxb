<?php

use App\Models\User;

it('renders the head component page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/layouts/head');

    $page->assertSee('Head Component')
        ->assertSee('Dynamic Title')
        ->assertSee('Meta Tags')
        ->assertSee('Title Template')
        ->assertSee('Tag Deduplication')
        ->assertNoJavaScriptErrors();
});
