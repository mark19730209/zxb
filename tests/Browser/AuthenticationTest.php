<?php

use App\Models\User;

it('can log in and reach the dashboard', function () {
    $user = User::factory()->create();

    $page = visit('/login');

    $page->assertSee('Inertia.js Kitchen Sink')
        ->assertNoJavaScriptErrors()
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->click('Log in')
        ->assertPathIs('/dashboard')
        ->assertSee('Dashboard')
        ->assertNoJavaScriptErrors();
});

it('shows validation errors with invalid credentials', function () {
    $page = visit('/login');

    $page->fill('email', 'invalid@example.com')
        ->fill('password', 'wrong-password')
        ->click('Log in')
        ->assertSee('These credentials do not match our records')
        ->assertNoJavaScriptErrors();
});

it('can log out', function () {
    $user = User::factory()->create();

    $page = visit('/login');

    $page->fill('email', $user->email)
        ->fill('password', 'password')
        ->click('Log in')
        ->assertPathIs('/dashboard')
        ->click('[data-test="sidebar-menu-button"]')
        ->click('[data-test="logout-button"]')
        ->assertPathIs('/login')
        ->assertNoJavaScriptErrors();
});
