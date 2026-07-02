# Phase 11 — Collections (grouping papers within a project)

> **Read [`00-conventions.md`](./00-conventions.md) first.** Strict TDD. Branch: `feature/collections`.

## Goal (plain English)

Inside a project, a user can group saved papers into named, colour-tagged **collections** (e.g. "Methods
papers", "Comparison"). A paper can belong to zero or more collections. Collections are per-project and owned
through the project — they are a lightweight organisational layer over the shared paper library, not a new
sharing boundary. This phase delivers the data model, CRUD, and authorization; the workspace sidebar UI that
renders them is wired up in **Phase 10** (do this phase first so Phase 10 has real data, not a stub).

**Depends on:** Phase 1 (ownership) + Phase 3 (papers reach a project through the `project_papers` pivot).
Do this **before** Phase 10.

## Questions to confirm before you start

1. **Colour set** — a fixed palette the user picks from, or a free-form hex value? A fixed set is easier to
   keep accessible and on-brand. *Recommended: a fixed named palette (e.g. `sage | teal | slate | clay |
   amber | plum`) stored as a string; map names → tokens on the frontend.* Confirm.
2. **Can the same paper be in multiple collections?** *Recommended: yes — a paper↔collection pivot, many
   collections per paper.* Confirm. (If they want strictly one collection per paper, we drop the pivot and put
   a nullable `collection_id` on `project_papers` instead — ask before choosing.)
3. **Is a paper required to be in the project before it can be added to a collection?** *Recommended: yes —
   only papers already attached to the project (a `project_papers` row exists) may be added to that project's
   collections.* Confirm.

## What already exists (after Phase 3)

- `Project belongsToMany(Paper, 'project_papers')` with pivot `user_id`, `status`, `added_at`.
- `Paper` is a canonical shared record; it has **no** owning project.
- Ownership reaches `User` through `Project` (Phase 1). Route bindings are scoped (`scopeBindings()` in
  `routes/projects.php`).

## Step 1 — Data model (TDD)

Write `tests/Feature/Collections/CollectionTest.php` **first** (cases in Step 3).

Tables/models (`php artisan make:model Collection -mf`):

- `collections`:
  ```
  project_id   foreignId -> projects, cascadeOnDelete
  user_id      foreignId -> users, cascadeOnDelete   // who created it (audit; ownership is via project)
  name         string
  color        string default 'sage'                 // one of the palette names (Q1)
  position     unsignedInteger default 0             // manual ordering in the sidebar
  timestamps
  ```
- `collection_paper` (pivot, only if Q2 = many-to-many):
  ```
  collection_id  foreignId -> collections, cascadeOnDelete
  paper_id       foreignId -> papers, cascadeOnDelete
  timestamps
  unique(['collection_id', 'paper_id'])
  ```

Relationships:
```php
// Collection
/** @return BelongsTo<Project, $this> */
public function project(): BelongsTo { return $this->belongsTo(Project::class); }

/** @return BelongsToMany<Paper, $this> */
public function papers(): BelongsToMany
{
    return $this->belongsToMany(Paper::class, 'collection_paper')->withTimestamps();
}

// Project
/** @return HasMany<Collection, $this> */
public function collections(): HasMany
{
    return $this->hasMany(Collection::class)->orderBy('position');
}
```

Use the `BelongsToUser` trait from Phase 1 on `Collection` if it fits the ownership pattern; otherwise authorize
purely via the bound project (pick one and keep the tests green — see Step 2).

`CollectionFactory`: `name` → `fake()->words(2, true)`, `color` → random palette name, `position` → 0.

## Step 2 — Policy

```bash
php artisan make:policy CollectionPolicy --model=Collection --no-interaction
```

A collection is owned through its project, so every check reduces to "does this user own the project?":
- `view`, `update`, `delete`, `addPaper`, `removePaper` → `$user->id === $collection->project->user_id`.

Controllers that receive the project via a **scoped** route binding (`/projects/{project}/collections/{collection}`)
should call `$this->authorize('update', $project)` on the **project** for create/reorder, and
`$this->authorize('update', $collection)` where a collection is bound. Keep it consistent with how Phase 3
authorizes paper actions against the bound project. Do **not** reintroduce per-paper ownership.

## Step 3 — Tests to write (RED)

`tests/Feature/Collections/CollectionTest.php`:
- `it('lets a user create a collection in their project')` — POST creates a `collections` row.
- `it('lists a projects collections ordered by position')`.
- `it('forbids creating a collection in another users project')` — 403.
- `it('lets a user rename and recolour a collection')` — PATCH updates `name` + `color`.
- `it('rejects an invalid colour')` — 422 when `color` is outside the palette.
- `it('lets a user add a project paper to a collection')` — pivot row created.
- `it('rejects adding a paper that is not attached to the project')` — 422/403 (Q3).
- `it('lets a user remove a paper from a collection')` — pivot row deleted; the paper stays in the project.
- `it('deletes a collection without deleting its papers')` — `collections` row gone, `papers` untouched.
- `it('forbids adding a paper to another users collection')` — 403.

## Step 4 — Routes, controllers, actions

Add to `routes/projects.php` (inside the existing `auth`/`verified`/`scopeBindings` group so
`{collection}` is scoped to `{project}`):
```php
Route::get('/{project}/collections', [CollectionController::class, 'index'])->name('collections.index');
Route::post('/{project}/collections', [CollectionController::class, 'store'])->name('collections.store');
Route::patch('/{project}/collections/{collection}', [CollectionController::class, 'update'])->name('collections.update');
Route::delete('/{project}/collections/{collection}', [CollectionController::class, 'destroy'])->name('collections.destroy');
Route::post('/{project}/collections/{collection}/papers', [CollectionController::class, 'addPaper'])->name('collections.papers.add');
Route::delete('/{project}/collections/{collection}/papers/{paper}', [CollectionController::class, 'removePaper'])->name('collections.papers.remove');
```

Thin `CollectionController` (validate → action → response). Actions in `app/Actions/Collections/`:
- `CreateCollectionAction::handle(Project $project, User $user, array $data): Collection`
- `AddPaperToCollectionAction::handle(Collection $collection, Paper $paper): void` — guards the "paper must be
  attached to the project" rule (Q3) via `$collection->project->papers()->whereKey($paper->id)->exists()`.

Requests:
- `StoreCollectionRequest` — `name` required max 100; `color` required, `Rule::in(palette)`.
- `UpdateCollectionRequest` — `name` sometimes max 100; `color` sometimes `Rule::in(palette)`.
- `AddPaperToCollectionRequest` — `paper_id` required exists.

Define the palette once in `config/collections.php` (`'colors' => ['sage','teal','slate','clay','amber','plum']`)
so requests and the frontend stay in sync; the config array is the single source of truth (do not hardcode the
list in the request).

`ProjectController::show` — eager-load collections with their paper IDs so the workspace can render them:
```php
'collections' => $project->collections()->with('papers:id')->get(),
```

## Step 5 — Frontend (minimal in this phase)

The full sidebar UI lands in Phase 10. In this phase, keep the surface small and accessible so the feature is
usable and tested before the redesign:
- A `CollectionsList` component (in `resources/js/components/`) that renders each collection with its colour
  dot + paper count, plus create/rename/delete controls. Reuse the existing UI kit (button, input, dialog,
  badge). Colour dots derive from the palette-name → token map; **never rely on colour alone** — always pair
  the dot with the collection name text (WCAG).
- Add-to-collection control on the paper card (a small labelled menu). Use Wayfinder + `router`/`useForm`,
  `preserveScroll`.

Run `php artisan wayfinder:generate` after adding routes.

> **Handoff to Phase 10:** Phase 10 relocates `CollectionsList` into the workspace project sidebar. Build it as
> a self-contained component here so Phase 10 only has to place it, not rewrite it.

## Done when

- [ ] A user can create, rename, recolour, reorder, and delete collections in their own project.
- [ ] Adding/removing a paper touches only the pivot; the shared paper and its project attachment are untouched.
- [ ] Only project-attached papers can be added to that project's collections (per Q3).
- [ ] All cross-user actions are 403; invalid colours are 422.
- [ ] `config/collections.php` is the single source of truth for the palette.
- [ ] Full suite green, Pint clean, Wayfinder regenerated, PR opened noting Q1–Q3 answers.
