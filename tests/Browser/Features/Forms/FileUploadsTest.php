<?php

use App\Models\User;

it('renders the file uploads demo page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/features/forms/file-uploads');

    $page->assertSee('File Uploads')
        ->assertSee('Upload State')
        ->assertNoJavaScriptErrors();
});
