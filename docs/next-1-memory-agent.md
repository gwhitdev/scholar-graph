# Next-1 — MemoryAgent (Qwen Hackathon Track 1)

> **Read [`00-conventions.md`](./00-conventions.md) first and follow it exactly.** Strict TDD. Branch:
> `feature/memory-agent`. This doc is written for an implementing agent with no product context — every
> decision has already been made below. Do not skip steps, do not reorder them, do not add anything not
> listed here.

## Goal (plain English)

ScholarGraph's chat already answers questions from papers the user adds to a **project**, but it forgets
everything once the conversation ends — there is no memory that survives across projects or sessions. This
phase adds a **persistent, per-user memory** that:

1. Extracts durable facts/preferences from each chat turn ("prefers concise bullet answers", "is researching
   workplace wellbeing interventions", "always wants APA-style citations").
2. Recalls the most relevant of those memories into the system prompt on every future synthesis — in any
   project, any session.
3. Forgets low-value memories over time so the recalled set stays small and relevant (bounded context
   budget).

This directly satisfies the Hackathon "Track 1: MemoryAgent" requirement: *"Build an Agent with persistent
memory that autonomously accumulates experience, remembers user preferences, and makes increasingly
accurate decisions across multi-turn, cross-session interactions... efficient memory storage and retrieval,
timely forgetting of outdated information, and recalling critical memories within limited context windows."*

**Depends on:** Phase 5 (monetisation — `CreateSynthesisAction`, `CreditService`) and the existing
`SynthesisService` chat pipeline. Both already exist on `main`.

## Decisions (already made — do not re-ask the user)

1. **Memory scope:** per-`User`, not per-`Project`. A memory recorded in one project is recallable in every
   other project the same user owns. This is what makes it "cross-session".
2. **Extraction model:** reuse the existing Qwen Cloud connection (`OpenRouterService`, configured via
   `config/services.php` → `services.openrouter.model`, default `qwen-plus`). No new package, no new API key.
3. **Retrieval scoring:** no embeddings/vector DB for this phase (keeps the "no new dependency" rule intact).
   Use simple keyword-overlap + importance + recency scoring. This can be swapped for embeddings in a later
   phase without changing the public `MemoryService` API.
4. **Forgetting:** importance decays on a schedule; memories are hard-deleted once importance drops below a
   threshold. This is intentionally simple (a float column + a scheduled command), not a fancy algorithm.
5. **Extraction timing:** synchronous is NOT acceptable (it would slow down every chat response and cost the
   user extra wait time). Extraction runs on the existing database queue (`QUEUE_CONNECTION=database`,
   already configured) via a queued job.
6. **Context budget:** recall at most 5 memories per synthesis call, each memory content capped at ~200
   characters at write time (enforced in the extraction prompt + a `Str::limit` safety net).

## Step 1 — Data model (TDD)

Create the test file first: `tests/Feature/Memory/UserMemoryModelTest.php`.

Test cases (write these first, watch them fail, then make them pass in the rest of this step):
```php
it('belongs to a user');
it('casts type to the MemoryType enum');
it('casts importance to a float');
```

Create the enum:
```bash
php artisan make:enum MemoryType --no-interaction
```
`app/Enums/MemoryType.php`:
```php
<?php

namespace App\Enums;

enum MemoryType: string
{
    case Preference = 'preference';
    case Fact = 'fact';
    case Decision = 'decision';
}
```
(TitleCase case names per the PHP conventions in `CLAUDE.md`.)

Create the migration and model:
```bash
php artisan make:model UserMemory -mf --no-interaction
```

`database/migrations/xxxx_create_user_memories_table.php`:
```php
Schema::create('user_memories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('type'); // MemoryType enum value
    $table->string('content', 255);
    $table->float('importance')->default(1.0);
    $table->timestamp('last_recalled_at')->nullable();
    $table->foreignId('source_project_id')->nullable()->constrained('projects')->nullOnDelete();
    $table->foreignId('source_chat_message_id')->nullable()->constrained('chat_messages')->nullOnDelete();
    $table->timestamps();

    $table->index(['user_id', 'importance']);
});
```

`app/Models/UserMemory.php`:
```php
<?php

namespace App\Models;

use App\Enums\MemoryType;
use Database\Factories\UserMemoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMemory extends Model
{
    /** @use HasFactory<UserMemoryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'content',
        'importance',
        'last_recalled_at',
        'source_project_id',
        'source_chat_message_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => MemoryType::class,
            'importance' => 'float',
            'last_recalled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Add to `User.php`:
```php
/**
 * @return HasMany<UserMemory, $this>
 */
public function memories(): HasMany
{
    return $this->hasMany(UserMemory::class);
}
```

Factory `database/factories/UserMemoryFactory.php` — `user_id` via `User::factory()`, `type` random
`MemoryType` case, `content` a short sentence via `fake()->sentence()`, `importance` default `1.0`.

Run `php artisan migrate` and `php artisan test --compact --filter=UserMemoryModelTest` until green.

## Step 2 — MemoryService (TDD)

Test file first: `tests/Unit/Memory/MemoryServiceTest.php` (unit test, no HTTP).

Test cases:
```php
it('creates a new memory when none similar exists');
it('bumps importance and updates content instead of duplicating an exact-match memory');
it('recalls memories ordered by keyword match, then importance, then recency');
it('limits recall to the given limit');
it('updates last_recalled_at on recalled memories');
it('decays importance for all memories by a fixed amount');
it('deletes memories whose importance drops to zero or below after decay');
```

`app/Services/Memory/MemoryService.php`:
```php
<?php

namespace App\Services\Memory;

use App\Enums\MemoryType;
use App\Models\User;
use App\Models\UserMemory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class MemoryService
{
    public const RECALL_LIMIT = 5;

    public const DECAY_AMOUNT = 0.1;

    public const FORGET_THRESHOLD = 0.0;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function remember(User $user, string $content, MemoryType $type, array $meta = []): UserMemory
    {
        $content = Str::limit($content, 255, '');

        $existing = $user->memories()
            ->where('type', $type->value)
            ->whereRaw('lower(content) = ?', [strtolower($content)])
            ->first();

        if ($existing) {
            $existing->update([
                'importance' => $existing->importance + 1,
                'last_recalled_at' => now(),
            ]);

            return $existing;
        }

        return $user->memories()->create([
            'type' => $type,
            'content' => $content,
            'importance' => 1.0,
            'source_project_id' => $meta['project_id'] ?? null,
            'source_chat_message_id' => $meta['chat_message_id'] ?? null,
        ]);
    }

    /**
     * @return Collection<int, UserMemory>
     */
    public function recall(User $user, string $query, int $limit = self::RECALL_LIMIT): Collection
    {
        $words = collect(preg_split('/\s+/', strtolower($query)))
            ->filter(fn (string $word): bool => strlen($word) > 3)
            ->unique()
            ->values();

        $memories = $user->memories()
            ->when($words->isNotEmpty(), function ($query) use ($words) {
                $query->where(function ($query) use ($words) {
                    foreach ($words as $word) {
                        $query->orWhereRaw('lower(content) like ?', ["%{$word}%"]);
                    }
                });
            })
            ->orderByDesc('importance')
            ->orderByDesc('last_recalled_at')
            ->limit($limit)
            ->get();

        // Fall back to the most important memories overall when nothing matches the query,
        // so the agent still has *some* context on a brand-new topic.
        if ($memories->isEmpty()) {
            $memories = $user->memories()
                ->orderByDesc('importance')
                ->orderByDesc('last_recalled_at')
                ->limit($limit)
                ->get();
        }

        $memories->each(fn (UserMemory $memory) => $memory->update(['last_recalled_at' => now()]));

        return $memories;
    }

    public function decay(): void
    {
        UserMemory::query()->decrement('importance', self::DECAY_AMOUNT);

        UserMemory::query()->where('importance', '<=', self::FORGET_THRESHOLD)->delete();
    }
}
```

Run `php artisan test --compact --filter=MemoryServiceTest` until green.

## Step 3 — Extraction job wired into the synthesis flow (TDD)

Test file first: `tests/Feature/Memory/ExtractMemoriesJobTest.php`.

Test cases:
```php
it('extracts and stores memories from the model response')
    // Http::fake the openrouter endpoint to return a JSON array of {type, content} objects
    // in the assistant message content, run the job, assertDatabaseHas('user_memories', [...]).
it('logs the extraction call via LogLlmCallAction with context_type memory_extraction');
it('does nothing when the model returns no memories (empty JSON array)');
it('does not throw when the model returns malformed JSON (logs and returns)');
```

Create the job:
```bash
php artisan make:job ExtractMemoriesJob --no-interaction
```

`app/Jobs/ExtractMemoriesJob.php`:
```php
<?php

namespace App\Jobs;

use App\Actions\Usage\LogLlmCallAction;
use App\Enums\MemoryType;
use App\Models\Project;
use App\Models\User;
use App\Services\Memory\MemoryService;
use App\Services\OpenRouterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExtractMemoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $question,
        public string $answer,
        public ?Project $project = null,
    ) {}

    public function handle(OpenRouterService $openRouter, MemoryService $memoryService, LogLlmCallAction $logLlmCall): void
    {
        $prompt = <<<'PROMPT'
Extract any durable facts or preferences about the USER (not the subject matter) from this exchange that
would be useful to remember in future, unrelated conversations. Examples: preferred answer format, research
interests, recurring constraints. Do NOT extract one-off facts about the papers discussed.

Return ONLY a JSON array, no prose, no markdown fences. Each item: {"type": "preference"|"fact"|"decision", "content": "short sentence, max 200 characters"}.
If nothing durable was revealed, return [].
PROMPT;

        $result = $openRouter->chat([
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'user', 'content' => "User asked: {$this->question}\n\nAssistant answered: {$this->answer}"],
        ], user: $this->user);

        $logLlmCall->handle($result, $this->user, 'memory_extraction', $this->project?->id);

        $items = json_decode(trim($result->content), true);

        if (! is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if (! isset($item['type'], $item['content']) || ! is_string($item['content'])) {
                continue;
            }

            $type = MemoryType::tryFrom($item['type']);

            if (! $type) {
                continue;
            }

            $memoryService->remember($this->user, $item['content'], $type, [
                'project_id' => $this->project?->id,
            ]);
        }
    }
}
```

Wire it into `app/Actions/Syntheses/CreateSynthesisAction.php` — after the synthesis + debit succeed,
dispatch the job with the question and the synthesis's answer:
```php
use App\Jobs\ExtractMemoriesJob;

// ...inside handle(), after $this->creditService->debit(...):
ExtractMemoriesJob::dispatch($user, $question, $synthesis->answer, $project);

return $synthesis;
```
(Check the actual column name on `Synthesis` for the assistant's answer text — read `app/Models/Synthesis.php`
and use whatever the existing attribute is called; do not guess a new column name.)

Run `php artisan test --compact --filter=ExtractMemoriesJobTest` until green.

## Step 4 — Recall wired into the chat prompt (TDD)

Test file first: `tests/Feature/Memory/SynthesisMemoryRecallTest.php`.

Test cases:
```php
it('includes a "What we remember about you" section in the system prompt when the user has memories');
it('omits the memory section entirely when the user has no memories');
it('recalls memories from a different project than the one being queried')
    // create a memory with source_project_id = project A, then synthesise in project B,
    // assert the prompt sent to OpenRouterService includes that memory's content.
```

Modify `app/Services/SynthesisService.php`:

1. Inject `MemoryService` via the constructor (add it alongside `OpenRouterService` and `LogLlmCallAction`).
2. In `buildPromptMessages()`, before calling `resolveSystemPrompt()`, recall memories:
   ```php
   $memories = $this->memoryService->recall($project->user, $question);
   ```
3. Change `resolveSystemPrompt(Project $project, string $paperContext = '')` to
   `resolveSystemPrompt(Project $project, string $paperContext = '', Collection $memories = new Collection)`
   and, right after the `$customPrompt`/`$negativePrompt` composition, build a memory block:
   ```php
   $memoryBlock = $memories->isNotEmpty()
       ? "## What We Remember About You\n".$memories->map(fn ($m) => "- {$m->content}")->implode("\n")
       : '';
   ```
   Append `$memoryBlock` the same way `$negativePrompt` is appended (only when non-empty, separated by
   `"\n\n"`), placed **before** the negative prompt so "do not" instructions stay last/most-recent.
4. Update the one call site in `buildPromptMessages()` to pass `$memories` through.

Run `php artisan test --compact --filter=SynthesisMemoryRecallTest`, then the whole `SynthesisServiceTest`
if one exists, to confirm no regression.

## Step 5 — Forgetting (scheduled decay command)

No new failing-test ceremony needed beyond a direct command test, since this is a thin wrapper over
`MemoryService::decay()` which is already tested in Step 2.

```bash
php artisan make:command DecayMemories --no-interaction
```
Signature `app:decay-memories`. Body: `app(MemoryService::class)->decay();` plus a console info line with
how many memories were deleted (query the count before calling decay, or have `decay()` return the delete
count — prefer changing `decay(): int` to return `UserMemory::query()->where(...)->delete()`'s result, and
update the Step 2 test accordingly).

Schedule it daily in `routes/console.php`, next to the existing monthly-credits line:
```php
Schedule::command('app:decay-memories')->daily();
```

Test file: `tests/Feature/Memory/DecayMemoriesCommandTest.php`:
```php
it('runs the decay command and reduces importance');
```

## Step 6 — Memory settings panel (TDD, frontend)

Backend first:
- `php artisan make:controller Settings/MemoryController --no-interaction`
- Routes in `routes/web.php` (inside the existing authenticated `settings` group — check how
  `settings/billing` is registered in Step 5 of Phase 5 and mirror it exactly):
  - `GET /settings/memory` → `MemoryController::index` → Inertia page `settings/memory` with the
    authenticated user's memories (`$request->user()->memories()->latest('importance')->get()`), each
    serialized as `{id, type, content, importance, last_recalled_at}`.
  - `DELETE /settings/memory/{memory}` → `MemoryController::destroy` — authorize the memory belongs to
    the current user (`abort_unless($memory->user_id === $request->user()->id, 403)`), delete it, redirect
    back.
- Test file `tests/Feature/Memory/MemorySettingsTest.php`:
  ```php
  it('shows the authenticated user their memories');
  it('does not show another user\'s memories');
  it('deletes a memory belonging to the user');
  it('forbids deleting another user\'s memory');
  ```
- Run `php artisan wayfinder:generate` after adding the routes.

Frontend:
- `resources/js/pages/settings/memory.tsx` — follow the exact structure of the existing
  `resources/js/pages/settings/billing.tsx` (layout, `<Head>`, settings nav). List memories grouped by
  `type`, each row shows `content`, a relative `last_recalled_at` (reuse whatever date-formatting
  utility/helper `billing.tsx` or similar pages already use), and a delete button using the existing
  `ui/button` + `ui/dialog` confirm pattern already in `resources/js/components/ui`. Empty state: a short
  sentence like "Nothing remembered yet — it builds up as you chat."
- Add a `Memory` entry to the settings navigation, matching how `Billing` is registered there.
- Accessibility: delete button must have `aria-label="Delete memory: {content}"`; confirm dialog must
  trap focus and close on Esc (reuse the existing dialog component, do not hand-roll).

## Step 7 — Alibaba Cloud deployment proof (NOT CODE — do last, manually)

This step cannot be done by an implementing agent alone; flag it back to the user rather than attempting it:

- The hackathon requires proof that the backend runs on an Alibaba Cloud service (ECS / Function Compute /
  Web App Service), linked from a `DEPLOYMENT.md` in the repo root pointing at the specific code file(s)
  using Alibaba Cloud APIs.
- This repo already calls a dashscope-compatible endpoint
  (`config/services.php` → `services.openrouter.base_url`, default
  `https://dashscope-intl.aliyuncs.com/compatible-mode/v1`) — that satisfies "uses Alibaba Cloud APIs" for
  the Qwen model calls themselves, but the **application backend** (Laravel) still needs to be deployed on
  Alibaba Cloud infrastructure, not just calling Alibaba's model API from elsewhere.
- Do not attempt cloud account setup or deployment as part of this coding phase — stop here and hand back
  to the user once Steps 1–6 are green.

## Done when

- [ ] `user_memories` table + model + factory exist; `MemoryService::remember`/`recall`/`decay` fully tested.
- [ ] Every synthesis queues an `ExtractMemoriesJob` that stores durable memories, logged via
      `LogLlmCallAction` with `context_type = 'memory_extraction'`.
- [ ] The system prompt includes a "What We Remember About You" section, populated across projects, capped
      at 5 memories, only when memories exist.
- [ ] A daily scheduled command decays and prunes stale memories.
- [ ] `/settings/memory` lets a user view and delete their own memories only.
- [ ] Full suite green (`php artisan test --compact`), Pint clean
      (`vendor/bin/pint --dirty --format agent`), Wayfinder regenerated, PR opened against `main` from
      `feature/memory-agent` noting this phase implements Hackathon Track 1 (MemoryAgent) and that Step 7
      (cloud deployment proof) is still outstanding and manual.
