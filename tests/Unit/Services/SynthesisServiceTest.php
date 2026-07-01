<?php

use App\Enums\MessageRole;
use App\Models\ChatMessage;
use App\Models\Paper;
use App\Models\Project;
use App\Models\Synthesis;
use App\Models\User;
use App\Services\OpenRouterService;
use App\Services\SynthesisService;
use Illuminate\Support\Facades\Http;

test('build context returns papers and messages', function () {
    $project = Project::factory()->create();
    Paper::factory()->for($project)->count(2)->create();
    ChatMessage::factory()->for($project)->count(3)->create();

    $service = new SynthesisService(new OpenRouterService('key', 'model'));
    $context = $service->buildContext($project);

    expect($context['papers'])->toHaveCount(2);
    expect($context['messages'])->toHaveCount(3);
});

test('build prompt messages contains system papers and user question', function () {
    $project = Project::factory()->create();
    Paper::factory()->for($project)->create([
        'title' => 'Sample Paper',
        'abstract' => 'Sample abstract',
    ]);

    $service = new SynthesisService(new OpenRouterService('key', 'model'));
    $messages = $service->buildPromptMessages($project, 'What is this about?');

    expect($messages[0]['role'])->toBe('system');
    expect($messages[0]['content'])->toContain('Sample Paper');
    expect($messages[1]['role'])->toBe('user');
    expect($messages[1]['content'])->toBe('What is this about?');
});

test('synthesise creates records and calls openrouter', function () {
    config(['services.openrouter.model' => 'qwen/test-model']);

    Http::fake([
        'openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Synthesised answer.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $paper = Paper::factory()->for($project)->create();

    $service = new SynthesisService(new OpenRouterService('key', 'qwen/test-model'));
    $synthesis = $service->synthesise($project, 'Summarise this paper.');

    expect($synthesis)->toBeInstanceOf(Synthesis::class);
    expect($synthesis->question)->toBe('Summarise this paper.');
    expect($synthesis->answer)->toBe('Synthesised answer.');
    expect($synthesis->model_used)->toBe('qwen/test-model');
    expect($synthesis->paper_ids)->toContain($paper->id);

    expect($project->chatMessages()->count())->toBe(2);
    expect($project->chatMessages()->where('role', MessageRole::User)->count())->toBe(1);
    expect($project->chatMessages()->where('role', MessageRole::Assistant)->count())->toBe(1);
});

test('synthesise includes chat history in prompt', function () {
    config(['services.openrouter.model' => 'qwen/test-model']);

    Http::fake([
        'openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Answer two.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    ChatMessage::factory()->for($project)->create([
        'role' => MessageRole::User,
        'content' => 'Previous question',
    ]);
    ChatMessage::factory()->for($project)->create([
        'role' => MessageRole::Assistant,
        'content' => 'Previous answer',
    ]);

    $service = new SynthesisService(new OpenRouterService('key', 'qwen/test-model'));
    $service->synthesise($project, 'Follow-up question');

    Http::assertSent(function ($request) {
        $messages = $request['messages'];

        return collect($messages)->contains(fn ($m) => $m['content'] === 'Previous question')
            && collect($messages)->contains(fn ($m) => $m['content'] === 'Previous answer')
            && collect($messages)->contains(fn ($m) => $m['content'] === 'Follow-up question');
    });
});
