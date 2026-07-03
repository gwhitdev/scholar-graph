<?php

use App\Models\ApiUsageLog;
use App\Models\LlmCall;
use App\Models\Paper;
use App\Models\Project;
use App\Models\SearchQuery;
use App\Models\User;
use App\Services\Admin\AdminMetricsService;

beforeEach(function (): void {
    $this->service = new AdminMetricsService;
});

it('counts users', function (): void {
    User::factory()->count(3)->create();

    expect($this->service->userCount())->toBe(3);
});

it('counts papers', function (): void {
    Paper::factory()->count(5)->create();

    expect($this->service->paperCount())->toBe(5);
});

it('counts saved papers via project_papers pivot', function (): void {
    $project = Project::factory()->create();
    $papers = Paper::factory()->count(2)->create();

    foreach ($papers as $paper) {
        $project->papers()->attach($paper, [
            'user_id' => $project->user_id,
            'status' => 'unread',
            'added_at' => now(),
        ]);
    }

    expect($this->service->savedPaperCount())->toBe(2);
});

it('returns top search terms', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    SearchQuery::create(['user_id' => $user->id, 'project_id' => $project->id, 'query' => 'machine learning', 'source' => 'openalex', 'result_count' => 10]);
    SearchQuery::create(['user_id' => $user->id, 'project_id' => $project->id, 'query' => 'machine learning', 'source' => 'openalex', 'result_count' => 5]);
    SearchQuery::create(['user_id' => $user->id, 'project_id' => $project->id, 'query' => 'neural networks', 'source' => 'openalex', 'result_count' => 3]);

    $result = $this->service->topSearchTerms();

    expect($result)->toHaveCount(2)
        ->and($result->first()->query)->toBe('machine learning')
        ->and($result->first()->count)->toBe(2);
});

it('splits api usage into internal and external', function (): void {
    $user = User::factory()->create();

    ApiUsageLog::create(['user_id' => $user->id, 'source' => 'internal', 'service' => 'app', 'endpoint' => '/dashboard', 'method' => 'GET', 'status_code' => 200, 'duration_ms' => 50]);
    ApiUsageLog::create(['user_id' => $user->id, 'source' => 'internal', 'service' => 'app', 'endpoint' => '/projects', 'method' => 'GET', 'status_code' => 200, 'duration_ms' => 30]);
    ApiUsageLog::create(['user_id' => $user->id, 'source' => 'external', 'service' => 'openalex', 'endpoint' => '/works', 'method' => 'GET', 'status_code' => 200, 'duration_ms' => 200]);

    $result = $this->service->apiUsageBySource();

    expect($result)->toBe(['internal' => 2, 'external' => 1]);
});

it('sums llm tokens and cost', function (): void {
    $user = User::factory()->create();

    LlmCall::factory()->for($user)->create(['prompt_tokens' => 100, 'completion_tokens' => 50, 'cost_usd' => 0.001]);
    LlmCall::factory()->for($user)->create(['prompt_tokens' => 200, 'completion_tokens' => 100, 'cost_usd' => 0.002]);

    $result = $this->service->llmUsageTotals();

    expect($result['prompt_tokens'])->toBe(300)
        ->and($result['completion_tokens'])->toBe(150)
        ->and(abs((float) $result['cost_usd'] - 0.003))->toBeLessThan(0.0001);
});

it('breaks llm usage down by model', function (): void {
    $user = User::factory()->create();

    LlmCall::factory()->for($user)->create(['model' => 'gpt-4', 'prompt_tokens' => 100, 'completion_tokens' => 50, 'cost_usd' => 0.01]);
    LlmCall::factory()->for($user)->create(['model' => 'gpt-4', 'prompt_tokens' => 200, 'completion_tokens' => 100, 'cost_usd' => 0.02]);
    LlmCall::factory()->for($user)->create(['model' => 'claude-3', 'prompt_tokens' => 150, 'completion_tokens' => 75, 'cost_usd' => 0.005]);

    $result = $this->service->llmUsageByModel();

    expect($result)->toHaveCount(2);

    $gpt4 = $result->firstWhere('model', 'gpt-4');
    expect($gpt4->prompt_tokens)->toBe(300)
        ->and($gpt4->completion_tokens)->toBe(150);

    $claude = $result->firstWhere('model', 'claude-3');
    expect($claude->prompt_tokens)->toBe(150);
});

it('returns per-user usage totals', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    LlmCall::factory()->for($user1)->create(['prompt_tokens' => 100, 'completion_tokens' => 50, 'cost_usd' => 0.01]);
    LlmCall::factory()->for($user2)->create(['prompt_tokens' => 200, 'completion_tokens' => 100, 'cost_usd' => 0.02]);

    $result = $this->service->perUserUsage();

    expect($result)->toHaveCount(2);

    $u1 = $result->firstWhere('id', $user1->id);
    expect($u1->total_prompt_tokens)->toBe(100)
        ->and(abs((float) $u1->total_cost_usd - 0.01))->toBeLessThan(0.0001);

    $u2 = $result->firstWhere('id', $user2->id);
    expect($u2->total_prompt_tokens)->toBe(200);
});
