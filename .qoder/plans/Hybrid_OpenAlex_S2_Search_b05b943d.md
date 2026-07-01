# Hybrid Search: OpenAlex + Semantic Scholar

Every task below follows strict **Red-Green-Refactor**: write the failing test(s) first, run them to confirm they fail, implement the minimum to pass, then refactor.

## Current state
- `SemanticScholarService` handles both search and paper lookup, cached, but rate-limited at 100 req/5 min.
- `papers` table already has: `semantic_scholar_id`, `title`, `abstract`, `year`, `authors` (jsonb), `doi`, `venue`, `pages`, `raw_metadata` (jsonb). The flat columns (`authors`, `doi`, `venue`, `pages`) were added in a prior migration.
- `PaperController::search()` calls S2 directly. Frontend `paper-search.tsx` sends S2-shaped data.
- `SynthesisService` extracts authors/DOI from `raw_metadata['authors']` and `raw_metadata['externalIds']['DOI']` (S2 format).
- Queue is `database`, cache is `database`.

---

## Task 1 -- Config + OpenAlex service entry

**Red**: Write `tests/Unit/Services/OpenAlexConfigTest.php`:
```php
<?php

use App\Services\OpenAlexSearchService;

test('openalex config resolves with defaults', function () {
    expect(config('services.openalex.base_url'))->toBe('https://api.openalex.org');
});

test('openalex search service can be resolved from container', function () {
    $service = app(OpenAlexSearchService::class);
    expect($service)->toBeInstanceOf(OpenAlexSearchService::class);
});
```

**Green**: Add to `config/services.php`:
```php
'openalex' => [
    'base_url' => env('OPENALEX_BASE_URL', 'https://api.openalex.org'),
    'mailto' => env('OPENALEX_MAILTO', ''),
],
```
Create empty `app/Services/OpenAlexSearchService.php` and register it as a singleton in `AppServiceProvider`. Add `OPENALEX_BASE_URL` and `OPENALEX_MAILTO` to `.env.example`.

**Refactor**: Run pint, verify all existing tests still pass.

---

## Task 2 -- Migrations + models for OpenAlex columns + enrichment sidecar

**Red**: Add the following tests to the **existing** `tests/Feature/Papers/PaperModelTest.php` (do NOT replace the file -- it already contains `paper belongs to a project` and cascade-delete tests that must be kept):
```php
<?php

use App\Models\Paper;
use App\Models\PaperEnrichment;
use App\Models\Project;

test('paper has openalex fields in fillable', function () {
    $paper = new Paper;
    expect($paper->getFillable())->toContain('openalex_id', 'cited_by_count', 'referenced_works');
});

test('paper does not have removed fields in fillable', function () {
    $paper = new Paper;
    expect($paper->getFillable())->not->toContain('raw_metadata', 'semantic_scholar_id');
});

test('referenced_works casts to array', function () {
    $paper = Paper::factory()->create([
        'referenced_works' => ['W123', 'W456'],
    ]);
    expect($paper->fresh()->referenced_works)->toBeArray()
        ->and($paper->fresh()->referenced_works)->toBe(['W123', 'W456']);
});

test('paper has enrichment hasone relationship', function () {
    $paper = Paper::factory()->create();
    $enrichment = PaperEnrichment::factory()->create(['paper_id' => $paper->id]);

    expect($paper->enrichment)->toBeInstanceOf(PaperEnrichment::class)
        ->and($paper->enrichment->id)->toBe($enrichment->id);
});
```

Write `tests/Feature/Papers/PaperEnrichmentModelTest.php`:
```php
<?php

use App\Models\Paper;
use App\Models\PaperEnrichment;

test('paper enrichment belongs to paper', function () {
    $paper = Paper::factory()->create();
    $enrichment = PaperEnrichment::factory()->create(['paper_id' => $paper->id]);

    expect($enrichment->paper->id)->toBe($paper->id);
});

test('deleting paper cascades enrichment', function () {
    $paper = Paper::factory()->create();
    PaperEnrichment::factory()->create(['paper_id' => $paper->id]);

    $paper->delete();

    expect(PaperEnrichment::count())->toBe(0);
});
```

**Green**:
- Migration A: add to `papers` -- `openalex_id` string(64) nullable indexed, `cited_by_count` integer nullable, `referenced_works` jsonb nullable. (Only these 3 columns -- `authors`, `doi`, `venue`, `pages` already exist from prior migration.)
- Migration B: create `paper_enrichments` table -- `id`, `paper_id` (foreign, cascade delete, unique), `semantic_scholar_id` string(64) nullable, `tldr` text nullable, `influential_citation_count` integer nullable, `related_paper_ids` jsonb nullable, `enriched_at` timestamp nullable, timestamps.
- Migration C: drop `raw_metadata` and `semantic_scholar_id` columns from `papers` (S2 data is being replaced by OpenAlex; enrichment sidecar handles S2 fields going forward).
- Update `Paper` model: add `openalex_id`, `cited_by_count`, `referenced_works` to `$fillable`; remove `raw_metadata` and `semantic_scholar_id` from `$fillable`; cast `referenced_works` as array; remove `raw_metadata` from `casts()`; add `hasOne(PaperEnrichment::class)`.
- Update `PaperFactory`: add `openalex_id`, `cited_by_count`, `referenced_works`; **remove** `semantic_scholar_id` and `raw_metadata` (columns dropped by Migration C -- must be done here to prevent every factory-based test from breaking).
- Create `PaperEnrichment` model + factory.

**Refactor**: Run `php artisan migrate`, run full test suite.

---

## Task 3 -- Build `OpenAlexSearchService`

**Red**: Write `tests/Unit/Services/OpenAlexSearchServiceTest.php`:
```php
<?php

use App\Services\OpenAlexSearchService;
use Illuminate\Support\Facades\Http;

test('search normalizes OpenAlex response to expected shape', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response([
            'results' => [[
                'id' => 'https://openalex.org/W2741809807',
                'doi' => 'https://doi.org/10.1038/nature12373',
                'title' => 'Deep Learning',
                'publication_year' => 2015,
                'cited_by_count' => 45000,
                'abstract_inverted_index' => ['Deep' => [0], 'learning' => [1, 7], 'is' => [2]],
                'authorships' => [
                    ['author' => ['display_name' => 'Yann LeCun']],
                    ['author' => ['display_name' => 'Yoshua Bengio']],
                ],
                'primary_location' => ['source' => ['display_name' => 'Nature']],
                'referenced_works' => ['https://openalex.org/W123'],
            ]],
            'meta' => ['count' => 1],
        ], 200),
    ]);

    $service = new OpenAlexSearchService('https://api.openalex.org', 'test@example.com');
    $results = $service->search('deep learning');

    expect($results)->toHaveCount(1);
    expect($results[0]['openalex_id'])->toBe('W2741809807');
    expect($results[0]['doi'])->toBe('10.1038/nature12373');
    expect($results[0]['title'])->toBe('Deep Learning');
    expect($results[0]['year'])->toBe(2015);
    expect($results[0]['authors'])->toBe(['Yann LeCun', 'Yoshua Bengio']);
    expect($results[0]['venue'])->toBe('Nature');
    expect($results[0]['cited_by_count'])->toBe(45000);
    expect($results[0]['referenced_works'])->toBe(['W123']);
    expect($results[0]['abstract'])->toBeString();
});

test('reconstruct abstract from inverted index', function () {
    $service = new OpenAlexSearchService('https://api.openalex.org', 'test@example.com');

    $index = [
        'Hello' => [0],
        'world' => [1],
        'foo' => [3],
        'bar' => [2],
    ];

    $abstract = $service->reconstructAbstract($index);
    expect($abstract)->toBe('Hello world bar foo');
});

test('search caches results and does not repeat api calls', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response([
            'results' => [[
                'id' => 'https://openalex.org/W1',
                'title' => 'Cached Paper',
                'publication_year' => 2024,
                'authorships' => [],
                'primary_location' => null,
                'abstract_inverted_index' => null,
                'referenced_works' => [],
                'cited_by_count' => 0,
                'doi' => null,
            ]],
            'meta' => ['count' => 1],
        ], 200),
    ]);

    $service = new OpenAlexSearchService('https://api.openalex.org', 'test@example.com');
    $service->search('cacheable query');
    $service->search('cacheable query');

    Http::assertSentCount(1);
});

test('getWork caches results', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response([
            'id' => 'https://openalex.org/W1',
            'title' => 'Single Work',
            'publication_year' => 2023,
            'authorships' => [],
            'primary_location' => null,
            'abstract_inverted_index' => null,
            'referenced_works' => [],
            'cited_by_count' => 10,
            'doi' => null,
        ], 200),
    ]);

    $service = new OpenAlexSearchService('https://api.openalex.org', 'test@example.com');
    $service->getWork('W1');
    $service->getWork('W1');

    Http::assertSentCount(1);
});

test('handles missing abstract gracefully', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response([
            'results' => [[
                'id' => 'https://openalex.org/W1',
                'title' => 'No Abstract Paper',
                'publication_year' => 2024,
                'authorships' => [],
                'primary_location' => null,
                'abstract_inverted_index' => null,
                'referenced_works' => [],
                'cited_by_count' => 0,
                'doi' => null,
            ]],
            'meta' => ['count' => 1],
        ], 200),
    ]);

    $service = new OpenAlexSearchService('https://api.openalex.org', 'test@example.com');
    $results = $service->search('test');

    expect($results[0]['abstract'])->toBeNull();
});

test('search sends mailto param for polite pool', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response(['results' => [], 'meta' => ['count' => 0]], 200),
    ]);

    $service = new OpenAlexSearchService('https://api.openalex.org', 'test@example.com');
    $service->search('test');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'mailto=test@example.com');
    });
});
```

**Green**: Implement `OpenAlexSearchService`:
- `search()`: `GET {base_url}/works?search={query}&per_page={limit}&page={page}&mailto={mailto}` -- normalize `authorships` to flat author name list, reconstruct abstract from `abstract_inverted_index`, extract DOI from `doi` field (strip `https://doi.org/` prefix), map `primary_location.source.display_name` to venue, map `cited_by_count`, map `referenced_works` (strip `https://openalex.org/` prefix from each work URL to get bare IDs like `W123`).
- `getWork()`: `GET {base_url}/works/{id}?mailto={mailto}` -- same normalization.
- `reconstructAbstract()`: **public** method (directly testable). OpenAlex stores `abstract_inverted_index` as `{word: [pos, pos, ...]}` (word-to-positions map). Build a position-indexed array first (position -> word), sort by position, then join with spaces.
- Wrap both in `Cache::remember` (search: 1h, getWork: 24h).

**Refactor**: Extract normalization to a private `normalizeWork(array $work)` method. Run pint.

---

## Task 4 -- Refactor `SemanticScholarService` to enrichment-only

**Red**: Replace `tests/Unit/Services/SemanticScholarServiceTest.php` entirely:
```php
<?php

use App\Services\SemanticScholarService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new SemanticScholarService('https://api.semanticscholar.org');
});

test('enrich returns tldr and influential citation count', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'paperId' => 'abc123',
            'tldr' => ['text' => 'This paper introduces deep learning methods.'],
            'influentialCitationCount' => 342,
        ], 200),
    ]);

    $result = $this->service->enrich('10.1038/nature12373');

    expect($result)->toBeArray()
        ->and($result['tldr'])->toBe('This paper introduces deep learning methods.')
        ->and($result['influential_citation_count'])->toBe(342);
});

test('enrich returns null on 429', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([], 429),
    ]);

    $result = $this->service->enrich('10.1038/test');

    expect($result)->toBeNull();
});

test('enrich returns null on any http error', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response(['error' => 'server error'], 500),
    ]);

    $result = $this->service->enrich('10.1038/test');

    expect($result)->toBeNull();
});

test('enrich caches successful results', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'tldr' => ['text' => 'Cached summary.'],
            'influentialCitationCount' => 100,
        ], 200),
    ]);

    $this->service->enrich('10.1038/test');
    $this->service->enrich('10.1038/test');

    Http::assertSentCount(1);
});

test('enrich does not cache null results', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([], 429),
    ]);

    $this->service->enrich('10.1038/test');
    $this->service->enrich('10.1038/test');

    Http::assertSentCount(2);
});

test('getRelatedPapers returns normalized list', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'recommendedPapers' => [
                [
                    'paperId' => 'rec1',
                    'title' => 'Related Paper 1',
                    'year' => 2022,
                    'authors' => [['name' => 'Author A']],
                ],
                [
                    'paperId' => 'rec2',
                    'title' => 'Related Paper 2',
                    'year' => 2023,
                    'authors' => [['name' => 'Author B']],
                ],
            ],
        ], 200),
    ]);

    $results = $this->service->getRelatedPapers('abc123', 5);

    expect($results)->toHaveCount(2);
    expect($results[0]['semantic_scholar_id'])->toBe('rec1');
    expect($results[0]['title'])->toBe('Related Paper 1');
    expect($results[0]['authors'])->toBe(['Author A']);
});
```

**Green**: Refactor `SemanticScholarService`:
- Remove `search()` and `fetchPaper()` methods.
- Delete `app/Exceptions/SemanticScholarRateLimitException.php` (no remaining callers after search/fetchPaper removal).
- Add `enrich(string $doi): ?array` -- `GET /graph/v1/paper/DOI:{doi}?fields=tldr,influentialCitationCount`. Returns `['tldr' => ..., 'influential_citation_count' => ...]` or `null` on any failure.
- Add `getRelatedPapers(string $semanticScholarId, int $limit = 5): array` -- `GET /graph/v1/paper/{id}/recommendations?fields=title,year,authors&limit={limit}`.
- Cache both (24h TTL). `getRelatedPapers` can use `Cache::remember()`. **`enrich` must NOT use `Cache::remember()`** -- it caches null, which would prevent retries on 429. Use the manual pattern instead:
  ```php
  $cached = Cache::get($key);
  if ($cached !== null) { return $cached; }
  $result = /* HTTP call */;
  if ($result !== null) { Cache::put($key, $result, self::ENRICH_TTL); }
  return $result;
  ```

**Refactor**: Remove `normalizePaper()` if no longer needed. Update `AppServiceProvider` binding if constructor changes. Run pint.

---

## Task 5 -- Create `EnrichPaperJob`

**Red**: Write `tests/Feature/EnrichPaperJobTest.php`:
```php
<?php

use App\Jobs\EnrichPaperJob;
use App\Models\Paper;
use App\Models\PaperEnrichment;
use App\Services\SemanticScholarService;
use Illuminate\Support\Facades\Http;

test('job creates enrichment record on success', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'tldr' => ['text' => 'AI-generated summary.'],
            'influentialCitationCount' => 150,
        ], 200),
    ]);

    $paper = Paper::factory()->create(['doi' => '10.1038/test']);

    $job = new EnrichPaperJob($paper);
    $job->handle(app(SemanticScholarService::class));

    expect(PaperEnrichment::where('paper_id', $paper->id)->exists())->toBeTrue();
    $enrichment = PaperEnrichment::where('paper_id', $paper->id)->first();
    expect($enrichment->tldr)->toBe('AI-generated summary.')
        ->and($enrichment->influential_citation_count)->toBe(150)
        ->and($enrichment->enriched_at)->not->toBeNull();
});

test('job skips if already enriched', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'tldr' => ['text' => 'Already done.'],
            'influentialCitationCount' => 50,
        ], 200),
    ]);

    $paper = Paper::factory()->create(['doi' => '10.1038/test']);
    PaperEnrichment::factory()->create([
        'paper_id' => $paper->id,
        'enriched_at' => now(),
    ]);

    $job = new EnrichPaperJob($paper);
    $job->handle(app(SemanticScholarService::class));

    Http::assertNothingSent();
});

test('job does not create enrichment on rate limit', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([], 429),
    ]);

    $paper = Paper::factory()->create(['doi' => '10.1038/test']);

    $job = new EnrichPaperJob($paper);
    $job->handle(app(SemanticScholarService::class));

    expect(PaperEnrichment::where('paper_id', $paper->id)->exists())->toBeFalse();
});

test('job bails when paper has no doi', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'tldr' => ['text' => 'Should not reach.'],
            'influentialCitationCount' => 0,
        ], 200),
    ]);

    $paper = Paper::factory()->create(['doi' => null]);

    $job = new EnrichPaperJob($paper);
    $job->handle(app(SemanticScholarService::class));

    Http::assertNothingSent();
    expect(PaperEnrichment::where('paper_id', $paper->id)->exists())->toBeFalse();
});
```

**Green**: Create `app/Jobs/EnrichPaperJob.php`:
```php
class EnrichPaperJob implements ShouldQueue
{
    public function __construct(public Paper $paper) {}

    public function handle(SemanticScholarService $s2): void
    {
        if ($this->paper->enrichment?->enriched_at) {
            return;
        }

        // S2 enrichment requires a DOI -- openalex_id is not a valid S2 lookup key
        if (! $this->paper->doi) {
            return;
        }

        $data = $s2->enrich($this->paper->doi);

        if ($data === null) {
            $this->release(300);
            return;
        }

        $this->paper->enrichment()->updateOrCreate(
            ['paper_id' => $this->paper->id],
            array_merge($data, ['enriched_at' => now()])
        );
    }
}
```

**Refactor**: Run pint, verify queue config supports the job.

---

## Task 6 -- Wire `PaperController` + store action to OpenAlex

**Red**: Replace `tests/Feature/Papers/PaperControllerTest.php`:
```php
<?php

use App\Jobs\EnrichPaperJob;
use App\Models\Paper;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('guest cannot search papers', function () {
    $project = Project::factory()->create();

    $this->getJson(route('papers.search', $project))
        ->assertUnauthorized();
});

test('user can search papers', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response([
            'results' => [
                [
                    'id' => 'https://openalex.org/W2741809807',
                    'doi' => 'https://doi.org/10.1038/nature12373',
                    'title' => 'Deep Learning',
                    'publication_year' => 2015,
                    'cited_by_count' => 45000,
                    'abstract_inverted_index' => ['Deep' => [0], 'learning' => [1]],
                    'authorships' => [
                        ['author' => ['display_name' => 'Yann LeCun']],
                        ['author' => ['display_name' => 'Yoshua Bengio']],
                    ],
                    'primary_location' => ['source' => ['display_name' => 'Nature']],
                    'referenced_works' => [],
                ],
            ],
            'meta' => ['count' => 1],
        ], 200),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->getJson(route('papers.search', ['project' => $project, 'query' => 'deep learning']));

    $response->assertOk()
        ->assertJsonStructure([
            ['openalex_id', 'title', 'abstract', 'year', 'authors', 'doi', 'venue', 'cited_by_count'],
        ])
        ->assertJsonFragment([
            'openalex_id' => 'W2741809807',
            'title' => 'Deep Learning',
            'year' => 2015,
        ]);
});

test('user can add a paper to own project', function () {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('papers.store', $project), [
            'title' => 'Test Paper',
            'abstract' => 'An abstract',
            'year' => 2023,
            'openalex_id' => 'W2741809807',
            'authors' => ['Alice Smith', 'Bob Jones'],
            'doi' => '10.1038/test',
            'venue' => 'Nature',
            'cited_by_count' => 100,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('papers', [
        'project_id' => $project->id,
        'title' => 'Test Paper',
        'openalex_id' => 'W2741809807',
        'doi' => '10.1038/test',
        'venue' => 'Nature',
        'cited_by_count' => 100,
    ]);

    $paper = Paper::where('openalex_id', 'W2741809807')->first();
    expect($paper->authors)->toBe(['Alice Smith', 'Bob Jones']);

    Queue::assertPushed(EnrichPaperJob::class);
});

test('adding same paper twice does not create duplicate', function () {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $data = [
        'title' => 'Test Paper',
        'abstract' => 'An abstract',
        'year' => 2023,
        'openalex_id' => 'W2741809807',
    ];

    $this->actingAs($user)
        ->post(route('papers.store', $project), $data);

    $this->actingAs($user)
        ->post(route('papers.store', $project), $data);

    $this->assertDatabaseCount('papers', 1);
});

test('user cannot add paper to another users project', function () {
    $user = User::factory()->create();
    $otherProject = Project::factory()->create();

    $this->actingAs($user)
        ->post(route('papers.store', $otherProject), [
            'title' => 'Test Paper',
            'openalex_id' => 'W2741809807',
        ])
        ->assertForbidden();
});

test('user can delete own project paper', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $paper = Paper::factory()->for($project)->create();

    $this->actingAs($user)
        ->delete(route('papers.destroy', [$project, $paper]))
        ->assertRedirect();

    $this->assertDatabaseMissing('papers', ['id' => $paper->id]);
});

test('user can trigger enrichment on a paper', function () {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $paper = Paper::factory()->for($project)->create(['doi' => '10.1038/test']);

    $this->actingAs($user)
        ->postJson(route('papers.enrich', [$project, $paper]))
        ->assertStatus(202);

    Queue::assertPushed(EnrichPaperJob::class);
});

test('user cannot trigger enrichment on another users paper', function () {
    $user = User::factory()->create();
    $otherProject = Project::factory()->create();
    $paper = Paper::factory()->for($otherProject)->create(['doi' => '10.1038/test']);

    $this->actingAs($user)
        ->postJson(route('papers.enrich', [$otherProject, $paper]))
        ->assertForbidden();
});

test('user cannot enrich paper from different project via own project', function () {
    $user = User::factory()->create();
    $ownProject = Project::factory()->for($user)->create();
    $otherProject = Project::factory()->create();
    $otherPaper = Paper::factory()->for($otherProject)->create(['doi' => '10.1038/test']);

    $this->actingAs($user)
        ->postJson(route('papers.enrich', [$ownProject, $otherPaper]))
        ->assertForbidden();
});
```

**Green**:
- `PaperController::search()`: inject `OpenAlexSearchService` instead of `SemanticScholarService`. Remove S2 rate-limit catch and remove `SemanticScholarRateLimitException` import (class deleted in Task 4).
- `StorePaperRequest`: replace `semantic_scholar_id` with `openalex_id`; add `cited_by_count` (`nullable`, `integer`, `min:0`), `referenced_works` (`nullable`, `array`); remove `raw_metadata`. **Also add rules for `authors` (`nullable`, `array`), `authors.*` (`string`, `max:500`), `doi` (`nullable`, `string`, `max:255`), and `venue` (`nullable`, `string`, `max:500`)** -- the current rules never validated these, so `$request->validated()` has been silently dropping them; without the `doi` rule the paper saves with a null DOI and `EnrichPaperJob` bails, breaking enrichment end-to-end.
- `SavePaperToProjectAction`: dedup on `openalex_id`; dispatch `EnrichPaperJob` only when `$paper->wasRecentlyCreated` is true (avoid dispatching on duplicate finds).
- Add `PaperController::enrich()` method + route in `routes/projects.php` (confirmed loaded): `Route::post('/{project}/papers/{paper}/enrich', [PaperController::class, 'enrich'])->name('papers.enrich')` -- must verify both ownership guards (matching `destroy()` pattern): `abort_unless($project->user_id === auth()->id(), 403)` and `abort_unless($paper->project_id === $project->id, 403)`. Dispatches `EnrichPaperJob`, returns 202 Accepted.
- Run `php artisan wayfinder:generate` to regenerate TypeScript route bindings (frontend Task 8 imports from `@/actions/.../PaperController`).
- `PaperFactory` already updated in Task 2 (columns removed there to avoid breakage between migrations and factory sync).
- `ProjectController::show()`: eager-load `papers.enrichment` relationship when building Inertia props (`$project->load('papers.enrichment')` or equivalent) so frontend receives enrichment data.

**Refactor**: Run pint, full test suite.

---

## Task 7 -- Update `SynthesisService` for OpenAlex-sourced papers

**Red**: Replace the two affected tests in `tests/Unit/Services/SynthesisServiceTest.php`:

Replace the old `build prompt includes authors year and doi from raw metadata` test:
```php
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
```

Replace the old `build prompt omits missing metadata gracefully` test:
```php
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
```

**Green**: Update `SynthesisService`:
```php
protected function extractAuthors(Paper $paper): string
{
    if (! empty($paper->authors)) {
        return implode(', ', $paper->authors);
    }
    return '';
}

protected function extractDoi(Paper $paper): string
{
    return $paper->doi ?? '';
}
```

**Refactor**: Run pint, verify all synthesis tests pass.

---

## Task 8 -- Update frontend components

**Red**: (TypeScript compilation serves as the "failing test" for frontend changes -- `npx tsc --noEmit` must pass.)

**Green**:
- Run `php artisan wayfinder:generate` first (if not done in Task 6) so the enrich route TypeScript import exists.
- `paper-search.tsx`: Replace `PaperResult.semantic_scholar_id` with `openalex_id`; add `cited_by_count`. Update hidden form fields: add `<input type="hidden" name="cited_by_count" value={result.cited_by_count} />` (and similarly for `openalex_id`, `authors`, `doi`, `venue`, `referenced_works`). Change description to "Find papers via OpenAlex". Remove S2 rate-limit alert.
- `paper-card.tsx`: Replace `semantic_scholar_id` with `openalex_id`. Add optional `enrichment` object. Show TLDR section when available. Show citation count. Add "Get AI Summary" button that POSTs to enrich endpoint (imported via Wayfinder from `@/actions/App/Http/Controllers/PaperController`) when enrichment is null.
- **Async enrichment UX (deliberate scope decision)**: the enrich endpoint returns 202 and the job runs on the queue, so the TLDR is not available immediately. After a successful POST, switch the button to a disabled "Summary requested..." state and start polling the page props with Inertia's `usePoll` (e.g. every 5s, `only: ['papers']`), stopping once the paper's `enrichment` is non-null (or after ~60s as a cap). If polling is deemed too much for v1, the accepted fallback is: keep the pending state and let the TLDR appear on next page reload -- but this must be chosen consciously, not by omission.
- `show.tsx`: Update `Paper` interface to match new shape. Pass enrichment through.
- `chat-thread.tsx` / `sources-badge.tsx`: Update `Paper` interface (replace `semantic_scholar_id` with `openalex_id`).

**Refactor**: Run `npx tsc --noEmit`, verify no errors. Run `npm run build`.

---

## Execution order (dependency chain)

1. Task 1 (config) -- foundation
2. Task 2 (migrations + models) -- depends on Task 1
3. Task 3 (OpenAlexSearchService) -- depends on Tasks 1 + 2
4. Task 4 (refactor S2 to enrichment) -- depends on Task 2
5. Task 5 (EnrichPaperJob) -- depends on Tasks 2 + 4
6. Task 6 (controller + store wiring) -- depends on Tasks 3 + 5
7. Task 7 (SynthesisService) -- depends on Task 2
8. Task 8 (frontend) -- depends on Task 6
