<?php

use App\Models\User;

it('promotes a user to admin by email', function (): void {
    $user = User::factory()->create(['email' => 'admin@example.com']);

    $this->artisan('app:make-admin', ['email' => 'admin@example.com'])
        ->assertSuccessful()
        ->expectsOutput('User admin@example.com is now an admin.');

    expect($user->fresh()->isAdmin())->toBeTrue();
});

it('fails gracefully when user not found', function (): void {
    $this->artisan('app:make-admin', ['email' => 'nobody@example.com'])
        ->assertFailed()
        ->expectsOutput('No user found with email: nobody@example.com');
});
