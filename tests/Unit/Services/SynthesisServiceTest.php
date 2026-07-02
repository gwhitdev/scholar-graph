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
    expect($messages[0]['content'])->toContain('Sample abstract');
    expect($messages[0]['content'])->toContain('Response Guidelines');
    expect($messages[1]['role'])->toBe('user');
    expect($messages[1]['content'])->toBe('What is this about?');
});

test('build prompt includes authors year and doi from flat columns', function () {
    $project = Project::factory()->create();
    Paper::factory()->for($project)->create([
        'title' => 'Positive Psychology',
        'abstract' => 'An introduction.',
        'year' => 2000,
        'authors' => ['Seligman, M. E. P.', 'Csikszentmihalyi, M.'],
        'doi' => '10.1037/0003-066X.55.1.5',
    ]);

    $service = new SynthesisService(new OpenRouterService('key', 'model'));
    $messages = $service->buildPromptMessages($project, 'Summarise this.');

    $systemContent = $messages[0]['content'];
    expect($systemContent)->toContain('Authors: Seligman, M. E. P., Csikszentmihalyi, M.');
    expect($systemContent)->toContain('Year: 2000');
    expect($systemContent)->toContain('DOI: 10.1037/0003-066X.55.1.5');
});

test('build prompt omits missing metadata gracefully', function () {
    $project = Project::factory()->create();
    Paper::factory()->for($project)->create([
        'title' => 'Bare Paper',
        'abstract' => null,
        'year' => null,
        'authors' => null,
        'doi' => null,
    ]);

    $service = new SynthesisService(new OpenRouterService('key', 'model'));
    $messages = $service->buildPromptMessages($project, 'What?');

    $systemContent = $messages[0]['content'];
    expect($systemContent)->toContain('Title: Bare Paper');
    expect($systemContent)->toContain('No abstract available');
    expect($systemContent)->not->toContain('Authors:');
    expect($systemContent)->not->toContain('Year:');
    expect($systemContent)->not->toContain('DOI:');
});

test('build prompt uses fallback system prompt when no papers exist', function () {
    $project = Project::factory()->create();

    $service = new SynthesisService(new OpenRouterService('key', 'model'));
    $messages = $service->buildPromptMessages($project, 'What is psychology?');

    expect($messages[0]['content'])->toContain('No papers are available');
});

test('resolve system prompt uses global prompt when enabled', function () {
    $user = User::factory()->create([
        'global_system_prompt' => 'You are a global research assistant.',
    ]);
    $project = Project::factory()->for($user)->create([
        'use_global_prompt' => true,
        'system_prompt' => null,
    ]);

    $service = new SynthesisService(new OpenRouterService('key', 'model'));
    $resolved = $service->resolveSystemPrompt($project, 'Some paper context');

    expect($resolved)->toContain('You are a global research assistant.');
    expect($resolved)->toContain('Some paper context');
});

test('resolve system prompt uses project prompt when global disabled', function () {
    $user = User::factory()->create([
        'global_system_prompt' => 'You are a global research assistant.',
    ]);
    $project = Project::factory()->for($user)->create([
        'use_global_prompt' => false,
        'system_prompt' => 'You are a project-specific assistant.',
    ]);

    $service = new SynthesisService(new OpenRouterService('key', 'model'));
    $resolved = $service->resolveSystemPrompt($project, 'Some paper context');

    expect($resolved)->toContain('You are a project-specific assistant.');
    expect($resolved)->not->toContain('You are a global research assistant.');
});

test('resolve system prompt composes both global and project prompts', function () {
    $user = User::factory()->create([
        'global_system_prompt' => 'You are a global research assistant.',
    ]);
    $project = Project::factory()->for($user)->create([
        'use_global_prompt' => true,
        'system_prompt' => 'Focus on machine learning papers.',
    ]);

    $service = new SynthesisService(new OpenRouterService('key', 'model'));
    $resolved = $service->resolveSystemPrompt($project, 'Some paper context');

    expect($resolved)->toContain('You are a global research assistant.');
    expect($resolved)->toContain('Focus on machine learning papers.');
});

test('resolve system prompt appends negative prompt', function () {
    $user = User::factory()->create([
        'global_negative_prompt' => 'Do not use bullet points.',
    ]);
    $project = Project::factory()->for($user)->create([
        'use_global_prompt' => true,
        'negative_prompt' => 'Do not include a references section.',
    ]);

    $service = new SynthesisService(new OpenRouterService('key', 'model'));
    $resolved = $service->resolveSystemPrompt($project, 'Some paper context');

    expect($resolved)->toContain('Do NOT');
    expect($resolved)->toContain('Do not use bullet points.');
    expect($resolved)->toContain('Do not include a references section.');
});

test('resolve system prompt uses only project negative prompt when global disabled', function () {
    $user = User::factory()->create([
        'global_negative_prompt' => 'Do not use bullet points.',
    ]);
    $project = Project::factory()->for($user)->create([
        'use_global_prompt' => false,
        'negative_prompt' => 'Do not include a references section.',
    ]);

    $service = new SynthesisService(new OpenRouterService('key', 'model'));
    $resolved = $service->resolveSystemPrompt($project, 'Some paper context');

    expect($resolved)->toContain('Do NOT');
    expect($resolved)->not->toContain('Do not use bullet points.');
    expect($resolved)->toContain('Do not include a references section.');
});

test('resolve system prompt uses default when no custom prompts set', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create([
        'use_global_prompt' => true,
        'system_prompt' => null,
    ]);

    $service = new SynthesisService(new OpenRouterService('key', 'model'));
    $resolved = $service->resolveSystemPrompt($project, 'Some paper context');

    expect($resolved)->toContain('precise, scholarly research assistant');
    expect($resolved)->toContain('Response Guidelines');
});

test('resolve system prompt handles no papers with custom prompt', function () {
    $user = User::factory()->create([
        'global_system_prompt' => 'Custom instructions here.',
    ]);
    $project = Project::factory()->for($user)->create([
        'use_global_prompt' => true,
    ]);

    $service = new SynthesisService(new OpenRouterService('key', 'model'));
    $resolved = $service->resolveSystemPrompt($project, '');

    expect($resolved)->toContain('Custom instructions here.');
    expect($resolved)->toContain('No papers are available');
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
