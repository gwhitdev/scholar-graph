<?php

use App\Models\Project;
use App\Models\User;
use App\Services\Billing\CreditService;
use Database\Seeders\PlanSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(PlanSeeder::class);

    config([
        'services.openrouter.model' => 'qwen/test-model',
        'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
    ]);

    Http::fake([
        'openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => 'A helpful answer.']],
            ],
        ], 200),
    ]);
});

it('debits one credit per synthesis', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('chat.store', $project), [
            'question' => 'What does this paper conclude?',
        ])
        ->assertRedirect();

    expect(app(CreditService::class)->balance($user))->toBe(49);
});

it('blocks synthesis when balance is zero', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    // Drain all credits
    $user->wallet->update(['balance' => 0]);

    $this->actingAs($user)
        ->post(route('chat.store', $project), [
            'question' => 'What does this paper conclude?',
        ])
        ->assertRedirect();

    $this->assertDatabaseCount('syntheses', 0);
});

it('records an llm_spend transaction', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('chat.store', $project), [
            'question' => 'What does this paper conclude?',
        ]);

    $this->assertDatabaseHas('credit_transactions', [
        'user_id' => $user->id,
        'reason' => 'llm_spend',
        'delta' => -1,
    ]);
});
