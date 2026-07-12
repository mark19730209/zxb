<?php

use App\Models\Contact;
use App\Models\User;

it('renders contacts with infinite scroll', function () {
    $user = User::factory()->create();
    Contact::factory(20)->create();

    $this->actingAs($user);

    $page = visit('/contacts');

    $page->assertSee('Contacts')
        ->assertNoJavaScriptErrors();

    // Verify the page has scrollProps for InfiniteScroll
    $hasScrollProps = $page->script('
        !!document.getElementById("app").__vue_app__.config.globalProperties.$page.scrollProps?.contacts
    ');

    expect($hasScrollProps)->toBeTrue();

    // Verify contacts are rendered as direct children of InfiniteScroll items container
    $taggedCount = $page->script('document.querySelectorAll("[data-infinite-scroll-page]").length');
    expect($taggedCount)->toBeGreaterThan(1);
});
