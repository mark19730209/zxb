<?php

it('renders a 404 error page for non-existent URLs', function () {
    $page = visit('/this-page-does-not-exist');

    $page->assertSee('404')
        ->assertSee('Not Found')
        ->assertSee('Go Home')
        ->assertNoJavaScriptErrors();
});
