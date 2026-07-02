<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('logs an external api usage row when searching openalex', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response([
            'results' => [
                [
                    'id' => 'https://openalex.org/W123',
                    'title' => 'A Paper',
                    'authorships' => [],
                    'publication_year' => 2024,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('papers.search', ['project' => $project, 'query' => 'neural networks']))
        ->assertOk();

    $this->assertDatabaseHas('api_usage_logs', [
        'user_id' => $user->id,
        'source' => 'external',
        'service' => 'openalex',
        'method' => 'GET',
        'status_code' => 200,
    ]);
});

test('logs an external api usage row for openrouter chat', function () {
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

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('chat.store', $project), [
            'question' => 'What does this paper conclude?',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('api_usage_logs', [
        'user_id' => $user->id,
        'source' => 'external',
        'service' => 'openrouter',
        'method' => 'POST',
        'status_code' => 200,
    ]);
});
