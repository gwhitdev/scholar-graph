<?php

use App\Actions\Papers\GeneratePaperSummaryAction;
use App\Models\Paper;
use App\Services\OpenRouterService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->action = new GeneratePaperSummaryAction(
        new OpenRouterService('key', 'qwen-plus', 'https://llm.test')
    );
});

test('generates summary from title and abstract', function () {
    Http::fake([
        'llm.test/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'A concise generated summary.']],
            ],
        ], 200),
    ]);

    $paper = Paper::factory()->create([
        'title' => 'Deep Learning',
        'abstract' => 'A long abstract about neural networks.',
    ]);

    $summary = $this->action->handle($paper);

    expect($summary)->toBe('A concise generated summary.');

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);
        $userMessage = collect($body['messages'])->firstWhere('role', 'user')['content'] ?? '';

        return str_contains($userMessage, 'Deep Learning')
            && str_contains($userMessage, 'neural networks');
    });
});

test('returns null when abstract is missing', function () {
    Http::fake();

    $paper = Paper::factory()->create(['abstract' => null]);

    expect($this->action->handle($paper))->toBeNull();

    Http::assertNothingSent();
});

test('returns null when llm request fails', function () {
    Http::fake([
        'llm.test/*' => Http::response(['error' => 'overloaded'], 500),
    ]);

    $paper = Paper::factory()->create([
        'abstract' => 'An abstract.',
    ]);

    expect($this->action->handle($paper))->toBeNull();
});
