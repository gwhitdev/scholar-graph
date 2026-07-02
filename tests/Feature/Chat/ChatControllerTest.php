<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

test('guest cannot post chat message', function () {
    $project = Project::factory()->create();

    $this->post(route('chat.store', $project))
        ->assertRedirect(route('login'));
});

test('user can ask a question and synthesis is stored', function () {
    config([
        'services.openrouter.model' => 'qwen/test-model',
        'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
    ]);

    Http::fake([
        'openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'A helpful answer.',
                    ],
                ],
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

    $this->assertDatabaseHas('syntheses', [
        'project_id' => $project->id,
        'question' => 'What does this paper conclude?',
        'answer' => 'A helpful answer.',
        'model_used' => 'qwen/test-model',
    ]);

    $this->assertDatabaseHas('chat_messages', [
        'project_id' => $project->id,
        'role' => 'user',
        'content' => 'What does this paper conclude?',
    ]);

    $this->assertDatabaseHas('chat_messages', [
        'project_id' => $project->id,
        'role' => 'assistant',
        'content' => 'A helpful answer.',
    ]);
});

test('chat question requires at least three characters', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('chat.store', $project), [
            'question' => 'ab',
        ])
        ->assertSessionHasErrors('question');
});

test('user cannot post chat to another users project', function () {
    $user = User::factory()->create();
    $otherProject = Project::factory()->create();

    $this->actingAs($user)
        ->post(route('chat.store', $otherProject), [
            'question' => 'What does this paper conclude?',
        ])
        ->assertForbidden();
});

test('timeout shows user friendly error message', function () {
    Http::fake(function () {
        throw new ConnectionException('cURL error 28');
    });

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('chat.store', $project), [
            'question' => 'What does this paper conclude?',
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'The model took too long to respond. Please try again.',
        ]);
});
