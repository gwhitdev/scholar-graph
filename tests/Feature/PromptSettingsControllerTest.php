<?php

use App\Models\User;

test('guest cannot access prompt settings', function () {
    $this->get(route('prompt.edit'))->assertRedirect(route('login'));
});

test('user can view prompt settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('prompt.edit'))
        ->assertOk();
});

test('user can update global system prompt', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('prompt.update'), [
            'global_system_prompt' => 'You are a global research assistant.',
            'global_negative_prompt' => 'Do not use bullet points.',
        ])
        ->assertRedirect(route('prompt.edit'));

    $user->refresh();
    expect($user->global_system_prompt)->toBe('You are a global research assistant.');
    expect($user->global_negative_prompt)->toBe('Do not use bullet points.');
});

test('user can clear global system prompt', function () {
    $user = User::factory()->create([
        'global_system_prompt' => 'Old prompt text',
    ]);

    $this->actingAs($user)
        ->put(route('prompt.update'), [
            'global_system_prompt' => '',
        ])
        ->assertRedirect(route('prompt.edit'));

    $user->refresh();
    expect($user->global_system_prompt)->toBeNull();
});
