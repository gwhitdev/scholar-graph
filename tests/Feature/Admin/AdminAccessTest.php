<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('redirects guests from admin to login', function (): void {
    $this->get('/admin')
        ->assertRedirect('/login');
});

it('forbids non-admin users from admin', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

it('allows admin users into admin dashboard', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('admin/dashboard'));
});

it('allows admin users to view users page', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('admin/users'));
});

it('allows admin users to view usage page', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/usage')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('admin/usage'));
});
