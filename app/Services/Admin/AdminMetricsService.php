<?php

namespace App\Services\Admin;

use App\Models\ApiUsageLog;
use App\Models\LlmCall;
use App\Models\Paper;
use App\Models\SearchQuery;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminMetricsService
{
    public function userCount(): int
    {
        return User::count();
    }

    public function paperCount(): int
    {
        return Paper::count();
    }

    public function savedPaperCount(): int
    {
        return DB::table('project_papers')->count();
    }

    /**
     * @return Collection<int, object{query: string, count: int}>
     */
    public function topSearchTerms(int $limit = 20): Collection
    {
        return SearchQuery::query()
            ->select('query', DB::raw('count(*) as count'))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{internal: int, external: int}
     */
    public function apiUsageBySource(): array
    {
        return ApiUsageLog::query()
            ->select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->pluck('count', 'source')
            ->pipe(fn (Collection $c) => [
                'internal' => (int) $c->get('internal', 0),
                'external' => (int) $c->get('external', 0),
            ]);
    }

    /**
     * @return array{prompt_tokens: int, completion_tokens: int, cost_usd: float}
     */
    public function llmUsageTotals(): array
    {
        return LlmCall::query()
            ->select(
                DB::raw('coalesce(sum(prompt_tokens), 0) as prompt_tokens'),
                DB::raw('coalesce(sum(completion_tokens), 0) as completion_tokens'),
                DB::raw('coalesce(sum(cost_usd), 0) as cost_usd'),
            )
            ->first()
            ?->getAttributes() ?? ['prompt_tokens' => 0, 'completion_tokens' => 0, 'cost_usd' => 0.0];
    }

    /**
     * @return Collection<int, object{model: string, prompt_tokens: int, completion_tokens: int, cost_usd: float}>
     */
    public function llmUsageByModel(): Collection
    {
        return LlmCall::query()
            ->select(
                'model',
                DB::raw('sum(prompt_tokens) as prompt_tokens'),
                DB::raw('sum(completion_tokens) as completion_tokens'),
                DB::raw('sum(cost_usd) as cost_usd'),
            )
            ->groupBy('model')
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    public function perUserUsage(): Collection
    {
        return User::query()
            ->withCount('projects')
            ->select('users.*')
            ->leftJoin('llm_calls', 'users.id', '=', 'llm_calls.user_id')
            ->selectRaw('coalesce(sum(llm_calls.prompt_tokens), 0) as total_prompt_tokens')
            ->selectRaw('coalesce(sum(llm_calls.completion_tokens), 0) as total_completion_tokens')
            ->selectRaw('coalesce(sum(llm_calls.cost_usd), 0) as total_cost_usd')
            ->groupBy('users.id')
            ->get();
    }
}
