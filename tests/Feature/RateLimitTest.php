<?php

use App\Models\User;

test('global rate limiter returns 429 when exceeded', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    for ($i = 0; $i < 120; $i++) {
        $this->get(route('dashboard'));
    }

    $this->get(route('dashboard'))->assertTooManyRequests();
});
