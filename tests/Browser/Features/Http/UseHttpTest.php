<?php

use App\Models\User;

it('renders the useHttp page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/http/use-http');

    $page->assertSee('useHttp')
        ->assertSee('API Request Demo')
        ->assertSee('Reactive State')
        ->assertSee('useHttp vs useForm')
        ->assertNoJavaScriptErrors();
});
