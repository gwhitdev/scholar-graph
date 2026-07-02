# Phase 2 — Usage & LLM logging

> **Read [`00-conventions.md`](./00-conventions.md) first.** Strict TDD. Branch: `feature/usage-logging`.

## Goal (plain English)

We need real numbers for the admin portal and billing later: how many tokens each LLM call used, what it
cost, how long it took, what people searched for, and how often we hit external APIs. This phase adds the
logging tables and wires logging into the existing services **without breaking current behaviour**.

**Depends on:** Phase 1.

## Questions to confirm before you start

1. **`OpenRouterService::chat()` return type change — OK?**
   Today it returns a plain `string` (the content). To capture tokens/cost we want it to return a small
   `ChatResult` object instead. That touches `SynthesisService` and `GeneratePaperSummaryAction` and their
   tests. *Recommended: yes, change it.* The alternative is a parallel `chatWithUsage()` method that returns
   the object while `chat()` stays a string — more code, less clean. **Ask which the user prefers.**
2. **Does OpenRouter return cost in the response?** We plan to send `'usage' => ['include' => true]` in the
   request body and read `usage.cost`. If the user isn't sure, proceed but leave `cost_usd` nullable and note
   that cost can be backfilled from a pricing map later. (Do not block on this.)
3. **Log the full prompt/response text?** Storing prompts helps debugging but grows the DB and stores user
   content. *Recommended for MVP: store token counts + cost + duration only, NOT the raw prompt/response text.*
   Confirm with the user (privacy call).

## What already exists

- `app/Services/OpenRouterService.php` — `chat(array $messages, ?string $model = null): string`. It uses
  `Http::withToken()->timeout(30)->retry(1,500)->post('/chat/completions', ...)` and returns
  `choices.0.message.content`. It currently **discards** the `usage` object.
- `app/Services/SynthesisService.php` and `app/Actions/Papers/GeneratePaperSummaryAction.php` call `chat()`.
- `app/Services/OpenAlexSearchService.php`, `SemanticScholarService.php` make external HTTP calls.
- `PaperController::search()` runs the OpenAlex search.

## Step 1 — `llm_calls` table + model (TDD)

Write `tests/Unit/Models/LlmCallTest.php` → `it('belongs to a user')`, `it('casts cost to decimal')`.

```bash
php artisan make:model LlmCall -m --no-interaction
```
Migration columns:
```
id
user_id            -> foreignId nullable, nullOnDelete
model              string
context_type       string nullable      // 'synthesis' | 'chat' | 'paper_summary'
context_id         unsignedBigInteger nullable
prompt_tokens      integer nullable
completion_tokens  integer nullable
cost_usd           decimal(10, 6) nullable
duration_ms        integer nullable
status             string default 'success'   // 'success' | 'error'
timestamps
```
Model: `belongsTo(User::class)`, fillable all columns, cast `cost_usd` → `'decimal:6'`.

## Step 2 — `ChatResult` DTO + capture usage in `OpenRouterService` (TDD)

Adjust `tests/Unit/Services/OpenRouterServiceTest.php` (write the new expectation first):
- `it('returns content, tokens, cost and duration')` — fake HTTP returns both `choices[0].message.content`
  and `usage: { prompt_tokens: 10, completion_tokens: 20, cost: 0.0003 }`; assert the returned `ChatResult`
  has those values and a non-negative `durationMs`.
- Keep the existing timeout / retry / non-2xx-throws tests (update them to read `->content` if needed).

Create `app/Services/DTO/ChatResult.php`:
```php
<?php

namespace App\Services\DTO;

final readonly class ChatResult
{
    public function __construct(
        public string $content,
        public string $model,
        public ?int $promptTokens,
        public ?int $completionTokens,
        public ?float $costUsd,
        public int $durationMs,
    ) {}
}
```

Change `OpenRouterService::chat()` to return `ChatResult` (timing around the request):
```php
public function chat(array $messages, ?string $model = null): ChatResult
{
    $usedModel = $model ?? $this->model;
    $start = microtime(true);

    try {
        $response = Http::withToken($this->apiKey)->timeout(30)->retry(1, 500)
            ->post($this->baseUrl.'/chat/completions', [
                'model'    => $usedModel,
                'messages' => $messages,
                'usage'    => ['include' => true],   // ask OpenRouter to return cost (verify per Q2)
            ]);
    } catch (ConnectionException $e) {
        throw new OpenRouterTimeoutException('OpenRouter request timed out.', previous: $e);
    }

    if (! $response->successful()) {
        throw new OpenRouterException('OpenRouter request failed: '.$response->body());
    }

    $durationMs = (int) round((microtime(true) - $start) * 1000);

    return new ChatResult(
        content: $response->json('choices.0.message.content') ?? '',
        model: $usedModel,
        promptTokens: $response->json('usage.prompt_tokens'),
        completionTokens: $response->json('usage.completion_tokens'),
        costUsd: $response->json('usage.cost'),
        durationMs: $durationMs,
    );
}
```

## Step 3 — `LogLlmCallAction` + wire into callers (TDD)

```bash
php artisan make:class Actions/Usage/LogLlmCallAction --no-interaction
```
```php
public function handle(ChatResult $result, ?User $user, string $contextType, ?int $contextId): LlmCall
{
    return LlmCall::create([
        'user_id'           => $user?->id,
        'model'             => $result->model,
        'context_type'      => $contextType,
        'context_id'        => $contextId,
        'prompt_tokens'     => $result->promptTokens,
        'completion_tokens' => $result->completionTokens,
        'cost_usd'          => $result->costUsd,
        'duration_ms'       => $result->durationMs,
        'status'            => 'success',
    ]);
}
```
Update callers (their tests will go red, then green):
- `SynthesisService::synthesise()` — after the `chat()` call, use `$result->content` for the answer and call
  `LogLlmCallAction` with `contextType: 'synthesis'`, `contextId: $synthesis->id`, `user: $project->user`.
- `GeneratePaperSummaryAction` — same, `contextType: 'paper_summary'`.

Add to `tests/Feature/Chat/ChatControllerTest.php` (or a new test):
`it('records an llm_calls row for a synthesis')`.

## Step 4 — `search_queries` logging (TDD)

Write `tests/Feature/Papers/SearchLoggingTest.php` → `it('records a search_queries row per search')`
(fake the OpenAlex HTTP call, hit `papers.search`, assert one row with the query + result_count).

```bash
php artisan make:model SearchQuery -m --no-interaction
```
Columns: `id`, `user_id` foreignId, `project_id` foreignId nullable nullOnDelete, `query` text,
`source` string default `'openalex'`, `result_count` integer nullable, timestamps.
Log inside `PaperController::search()` after results are returned. Keep the controller thin — do the write in
a tiny `Actions/Usage/LogSearchQueryAction` if it grows beyond one line.

## Step 5 — Internal + external API usage (TDD)

```bash
php artisan make:model ApiUsageLog -m --no-interaction
```
Columns: `id`, `user_id` foreignId nullable nullOnDelete, `source` string (`'internal'|'external'`),
`service` string (`'openalex'|'semantic_scholar'|'openrouter'|'app'`), `endpoint` string,
`method` string nullable, `status_code` integer nullable, `duration_ms` integer nullable, timestamps.

**Internal** — middleware:
```bash
php artisan make:middleware LogApiUsage --no-interaction
```
Append it to the web group in `bootstrap/app.php` (`$middleware->web(append: [..., LogApiUsage::class])`).
It records authenticated requests: `source:'internal'`, `service:'app'`, `endpoint:$request->path()`,
`method`, `status_code`, `duration_ms`. **Skip** asset routes, `/up`, and unauthenticated requests to
avoid noise. Test: `it('logs an internal api usage row for an authenticated page view')`.

**External** — shared recorder:
Create `app/Support/ApiUsageRecorder.php` with a static `record(string $service, string $endpoint, ?int $status, int $durationMs): void`.
Call it once after each `Http` call in `OpenAlexSearchService`, `SemanticScholarService`, and
`OpenRouterService` (`source:'external'`). This keeps the three services DRY (reusable-component rule).
Test one of them: `it('logs an external api usage row when searching openalex')`.

## Step 6 — Finish

```bash
php artisan test --compact                 # whole suite: no regressions
vendor/bin/pint --dirty --format agent
```

## Done when

- [ ] Every successful LLM call writes an `llm_calls` row with tokens + duration (+ cost if available).
- [ ] `OpenRouterService::chat()` returns a `ChatResult`; callers updated; their tests green.
- [ ] Each search writes a `search_queries` row.
- [ ] Internal requests + external API calls write `api_usage_logs` rows via a shared recorder.
- [ ] Full suite green, Pint clean, PR opened noting the Q1/Q3 answers.
