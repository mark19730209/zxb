<?php

use App\Models\Contact;
use App\Models\User;

it('renders the wayfinder playground page', function () {
    Contact::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->get(route('features.forms.wayfinder'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Features/Forms/Wayfinder')
        ->has('sampleContact')
    );
});
