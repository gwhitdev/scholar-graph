# Phase 3 — Shared paper library (canonical papers + pivot)

> **Read [`00-conventions.md`](./00-conventions.md) first.** Strict TDD. Branch: `feature/shared-paper-library`.

## Goal (plain English)

Today a `Paper` belongs to exactly one project (`papers.project_id`). If two users save the same paper, we
store it twice and enrich (pay for AI summaries) twice. This phase makes a paper a **single canonical record
shared across all users**, deduplicated by `openalex_id`. Each project links to papers through a **pivot
table** (`project_papers`) that carries per-project state (who added it, its reading status). Enrichment is
computed once and shared by everyone.

**Depends on:** Phase 1. This changes the `papers` schema — do it before there's production data.

## Questions to confirm before you start (IMPORTANT — this changes the schema)

1. **Proceed with the canonical-paper migration at all?** If the user wants papers to stay private per user,
   STOP — this phase is skipped. *Recommended: proceed.*
2. **Dedup key — `openalex_id` only, or also fall back to `doi`?** Some papers may lack an `openalex_id`.
   *Recommended: dedup on `openalex_id` when present, else `doi`, else treat as unique (no dedup).* Confirm.
3. **Is there existing paper data in any database that must be preserved?** If yes, the backfill migration
   (Step 1b) is mandatory and must run before dropping columns. If it's all disposable dev data, we can skip
   the backfill and just reset. **Ask before dropping columns.**

## What already exists

- `Paper` model: `belongsTo(Project)`, `hasOne(PaperEnrichment)`. Fillable includes `project_id`, `added_at`.
- `PaperEnrichment` belongs to `Paper` (this stays — it becomes shared automatically, which is what we want).
- `SavePaperToProjectAction` currently creates a `Paper` tied to one project.
- `PaperController::destroy` deletes the paper.
- `Synthesis.paper_ids` is a JSON array of paper IDs — still valid after this phase (IDs don't change).
- `resources/js/components/paper-search.tsx` and `paper-card.tsx` render papers.

## Step 1 — Migrations (TDD, in three ordered files)

Write `tests/Feature/Papers/SharedPaperTest.php` FIRST with these cases (they fail until the model changes):
- `it('deduplicates the same openalex paper across two users')` — user A saves openalex_id `W1` to project A,
  user B saves `W1` to project B → `Paper::count()` is 1, `project_papers` has 2 rows.
- `it('shares enrichment across projects')` — enrich the paper once → both projects see the same
  `PaperEnrichment`.
- `it('detaching a paper from a project does not delete the shared paper')` — detach from project A → paper
  still exists and is still attached to project B.
- `it('stores per-project status on the pivot')` — default status `'unread'`, updatable to `'reading'`.

Migration **1** — `create_project_papers_table`:
```
project_id   foreignId -> projects, cascadeOnDelete
paper_id     foreignId -> papers, cascadeOnDelete
user_id      foreignId -> users, cascadeOnDelete
status       string default 'unread'      // unread | reading | read | excluded
added_at     timestamptz nullable
timestamps
unique(['project_id', 'paper_id'])
```

Migration **1b** — `backfill_project_papers` (ONLY if Q3 says preserve data): in `up()`, loop existing
`papers` rows and insert a pivot row using `papers.project_id`, the project's `user_id`, and `papers.added_at`.
Use a chunked query. Leave `down()` empty or reverse it.

Migration **2** — `make_papers_canonical` (run AFTER backfill):
- add `unique` index on `papers.openalex_id`
- `dropColumn(['project_id', 'added_at'])`

Create `app/Enums/PaperStatus.php`: cases `Unread='unread'`, `Reading='reading'`, `Read='read'`, `Excluded='excluded'`.

## Step 2 — Model changes

`Paper`:
- remove `project_id` and `added_at` from `$fillable`; remove the `project()` belongsTo.
- add:
```php
/** @return BelongsToMany<Project, $this> */
public function projects(): BelongsToMany
{
    return $this->belongsToMany(Project::class, 'project_papers')
        ->withPivot(['user_id', 'status', 'added_at'])
        ->withTimestamps();
}
```

`Project`:
```php
/** @return BelongsToMany<Paper, $this> */
public function papers(): BelongsToMany
{
    return $this->belongsToMany(Paper::class, 'project_papers')
        ->withPivot(['user_id', 'status', 'added_at'])
        ->withTimestamps();
}
```

Run the shared-paper test until GREEN.

## Step 3 — Update save + destroy + status (backend)

`SavePaperToProjectAction::handle(Project $project, array $data): Paper`:
```php
$paper = Paper::firstOrCreate(
    ['openalex_id' => $data['openalex_id']],   // dedup key (see Q2)
    $paperAttributes,                          // title, abstract, year, authors, doi, venue, cited_by_count, referenced_works
);

$project->papers()->syncWithoutDetaching([
    $paper->id => ['user_id' => $project->user_id, 'status' => PaperStatus::Unread->value, 'added_at' => now()],
]);

return $paper;
```

`PaperController::destroy` → `$project->papers()->detach($paper->id);` (do NOT delete the paper).

New status route + method. In `routes/projects.php` add:
```php
Route::patch('/{project}/papers/{paper}', [PaperController::class, 'updateStatus'])->name('papers.status');
```
`PaperController::updateStatus(UpdatePaperStatusRequest $request, Project $project, Paper $paper)` →
`$this->authorize('update', $project);` then `$project->papers()->updateExistingPivot($paper->id, ['status' => $validated['status']]);`
Request validates `status` is one of the `PaperStatus` values.

> **Update `PaperPolicy` from Phase 1:** `Paper` no longer has a single `project`. Authorize paper actions
> against the **project bound in the route** instead of `$paper->project`. Adjust the controller calls to
> `$this->authorize('update', $project)` (already shown above) and simplify/remove the now-invalid
> `$paper->project->user_id` checks in `PaperPolicy` (or delete `PaperPolicy` and always authorize via the
> project — pick one and keep the tests green).

`ProjectController::show` — load papers via the pivot including status + enrichment:
```php
$papers = $project->papers()->with('enrichment')->get();
// expose $paper->pivot->status to the frontend
```

## Step 4 — Frontend

- Pass the set of already-saved `openalex_id`s to `projects/show` and into `paper-search.tsx`. In the search
  results, if a result's `openalex_id` is already saved, disable "Add" and show **"Added ✓"** instead
  (prevents duplicate-add confusion).
- In `paper-card.tsx`, read `paper.pivot.status` and add a small accessible status selector
  (Unread / Reading / Read / Excluded) that `PATCH`es `papers.status` (use Wayfinder + `router`/`useForm`,
  `preserveScroll`). Use the existing UI kit; label it for screen readers.

Run `php artisan wayfinder:generate` after the route change.

## Step 5 — Finish

```bash
php artisan test --compact                 # includes updated paper tests
vendor/bin/pint --dirty --format agent
```

## Done when

- [ ] Same paper saved by two users = one `papers` row, two `project_papers` rows.
- [ ] Enrichment is shared across projects.
- [ ] Detaching removes only the pivot row, never the shared paper.
- [ ] Per-project reading status works and is settable from the UI.
- [ ] Search marks already-saved papers as "Added ✓".
- [ ] `PaperPolicy` updated for shared papers; ownership tests from Phase 1 still green.
- [ ] Full suite green, Pint clean, Wayfinder regenerated, PR opened noting Q1–Q3 answers.
