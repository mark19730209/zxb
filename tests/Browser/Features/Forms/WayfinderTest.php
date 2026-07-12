<?php

use App\Models\Contact;
use App\Models\User;

it('renders the Wayfinder demo page', function () {
    Contact::factory()->create();
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/wayfinder');

    $page->assertSee('Wayfinder')
        ->assertSee('Hardcoded URL')
        ->assertSee('Controller Action')
        ->assertSee('Named Route')
        ->assertNoJavaScriptErrors();
});

it('shows Wayfinder form and route parameter examples', function () {
    Contact::factory()->create();
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/wayfinder');

    $page->assertSee('With route parameters')
        ->assertSee('Precognition')
        ->assertNoJavaScriptErrors();
});
