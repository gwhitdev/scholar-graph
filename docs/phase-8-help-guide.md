# Phase 8 — Help guide (authenticated)

> **Read [`00-conventions.md`](./00-conventions.md) first.** Strict TDD. Branch: `feature/help-guide`.

## Goal (plain English)

An in-app **help centre** for logged-in users: categorised, searchable articles, managed by admins. It reuses
the block content engine (`BlockRenderer` + block components) built in Phase 7 so we don't duplicate it.

**Depends on:** Phase 7 (block components + the publish pattern).

## Questions to confirm before you start

1. **Reuse the Phase 7 block engine, or plain markdown for help articles?** *Recommended: reuse the block
   engine (consistency, no duplication).* Confirm.
2. **Search scope for MVP** — simple `ILIKE` on title/body, or Postgres full-text (GIN index)? *Recommended:
   `ILIKE` for MVP; full-text is a later optimisation.* Confirm.
3. **Is help visible to all authenticated users, or gated by plan?** *Recommended: all authenticated users.*
   Confirm.

## Step 1 — Data model (TDD)

Enum: reuse `PageStatus` from Phase 7 (`draft|published`).

Tables/models (`make:model X -m`):
- `help_categories`: `slug` string unique, `title` string, `sort` int default 0, timestamps.
- `help_articles`: `help_category_id` foreignId cascade, `slug` string unique, `title` string,
  `content` json nullable (same block array shape as Phase 7 `pages`), `status` string default `'draft'`,
  `sort` int default 0, timestamps. Cast `content` → `array`, `status` → `PageStatus`.

Relationships: `HelpCategory hasMany(HelpArticle)`; `HelpArticle belongsTo(HelpCategory)`.

## Step 2 — Tests to write (RED)

`tests/Feature/Help/HelpGuideTest.php`:
- `it('requires auth to view help')` — guest → redirect to login.
- `it('lists published articles grouped by category')`.
- `it('hides draft articles from users')` — a draft article's slug → 404 for a normal user.
- `it('returns search matches')` — search term hits title/body, returns the article.
- `it('lets an admin create and publish an article')`.
- `it('forbids non-admins from managing help')` (403).

## Step 3 — Backend

Reuse Phase 7's publish logic. If `PageService::publish/unpublish` is specific to `Page`, extract the shared
behaviour into a small `Publishable` trait or a generic `PublishService` so both `Page` and `HelpArticle`
use it (reusable-component rule — do not copy-paste).

User-facing routes (`routes/help.php`, require from `web.php`):
```php
Route::middleware(['auth', 'verified'])->prefix('help')->name('help.')->group(function () {
    Route::get('/', [HelpController::class, 'index'])->name('index');
    Route::get('/search', [HelpController::class, 'search'])->name('search');
    Route::get('/{category:slug}/{article:slug}', [HelpController::class, 'show'])->name('show');
});
```
`HelpController::show` 404s on draft articles for non-admins. `search` does `ILIKE` on
`title`/serialized content (per Q2) and returns matches.

Admin management (add to `routes/admin.php`):
```php
Route::resource('help-categories', AdminHelpCategoryController::class)->except(['show']);
Route::resource('help-articles', AdminHelpArticleController::class)->except(['show']);
Route::post('/help-articles/{help_article}/publish', [AdminHelpArticleController::class, 'publish'])->name('help-articles.publish');
```
Thin controllers → services. Requests validate slug uniqueness + kebab-case.

## Step 4 — Frontend

- `resources/js/pages/help/index.tsx` — category sidebar + search box + article list.
- `resources/js/pages/help/show.tsx` — renders the article via the **Phase 7 `BlockRenderer`** (import and reuse).
- Admin: `resources/js/pages/admin/help/` — category and article management reusing the Phase 7 block editor.
- Add a **"Help"** item to the authenticated nav. Add a small contextual "Need help?" link on the project
  page that deep-links to a relevant article and offers **"Contact support"** → Phase 6 ticket create.

Run `php artisan wayfinder:generate` after routes.

## Done when

- [ ] Logged-in users browse help by category, open articles, and search.
- [ ] Draft articles are hidden from normal users (404); admins can preview.
- [ ] Admins create/edit/publish categories + articles reusing the Phase 7 block engine (no duplication).
- [ ] "Help" nav item + a contextual help/support link exist.
- [ ] Full suite green, Pint clean, Wayfinder regenerated, PR opened noting Q1–Q3 answers.
