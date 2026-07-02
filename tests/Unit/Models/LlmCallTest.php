<?php

use App\Models\LlmCall;
use App\Models\User;

test('belongs to a user', function () {
    $user = User::factory()->create();
    $call = LlmCall::factory()->for($user)->create();

    expect($call->user)->toBeInstanceOf(User::class)
        ->and($call->user->id)->toBe($user->id);
});

test('casts cost to decimal', function () {
    $call = LlmCall::factory()->create(['cost_usd' => 0.123456]);

    expect($call->cost_usd)->toBe('0.123456');
});

test('stores the prompt text', function () {
    $call = LlmCall::factory()->create(['prompt' => 'Summarise this paper.']);

    expect($call->prompt)->toBe('Summarise this paper.');
});
