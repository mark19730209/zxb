<?php

use App\Models\User;

it('renders the Form component demo page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/form-component');

    $page->assertSee('Form Component')
        ->assertSee('Template Ref Methods')
        ->assertNoJavaScriptErrors();
});

it('shows slot props reactive state', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/form-component');

    $page->assertSee('Slot Props (Reactive State)')
        ->assertSee('processing')
        ->assertSee('isDirty')
        ->assertNoJavaScriptErrors();
});
