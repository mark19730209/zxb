<?php

use App\Models\User;

it('returns precognition headers for invalid data', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->withHeaders([
        'Precognition' => 'true',
        'Precognition-Validate-Only' => 'username',
    ])->postJson(route('features.forms.precognition'), [
        'username' => 'ab',
    ]);

    $response->assertStatus(422);
    $response->assertHeader('Precognition', 'true');
});

it('returns precognition headers for valid data', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->withHeaders([
        'Precognition' => 'true',
        'Precognition-Validate-Only' => 'username',
    ])->postJson(route('features.forms.precognition'), [
        'username' => 'validuser',
    ]);

    $response->assertSuccessfulPrecognition();
    $response->assertHeader('Precognition', 'true');
});
