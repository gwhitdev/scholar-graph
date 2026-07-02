# Phase 7 — CMS for public (non-authenticated) pages

> **Read [`00-conventions.md`](./00-conventions.md) first.** Strict TDD. Branch: `feature/cms`.

## Goal (plain English)

Let admins create and manage the **public marketing/legal pages** (home, about, pricing, terms, privacy…)
without touching code. Pages have a URL slug, a publish workflow (draft vs published), SEO metadata,
block-based content, uploadable media, and manageable header/footer menus. Published pages render to
non-authenticated visitors; drafts 404 for the public.

**Depends on:** Phase 4 (admin area).

## Questions to confirm before you start

1. **Content model — block-based JSON, or plain markdown?**
   Blocks (`heading`, `paragraph`, `image`, `cta`) are more flexible and reused by Phase 8; markdown is
   simpler. *Recommended: block-based JSON with markdown allowed inside paragraph blocks.* Confirm.
2. **Does the CMS own the home page (`/`)?** Today `/` renders `welcome.tsx`. Should a CMS page with slug
   `home` replace it (falling back to `welcome.tsx` if none exists)? *Recommended: yes, CMS owns `/` when a
   published `home` page exists.* Confirm — this affects routing.
3. **Media storage** — local `public` disk for MVP, or S3-compatible now? *Recommended: local `public` disk;
   S3 later.* Confirm.
4. **Reserved slugs** — the public catch-all must NOT shadow app routes (`/projects`, `/admin`, `/settings`,
   `/support`, `/help`, `/login`, etc.). Confirm the reserved list so page creation rejects those slugs.

## Step 1 — Data model (TDD)

Enum `app/Enums/PageStatus.php`: `Draft='draft'`, `Published='published'`.

Tables/models (`make:model X -m`):
- `pages`: `slug` string unique, `title` string, `content` json nullable (array of blocks),
  `status` string default `'draft'`, `seo_title` string nullable, `seo_description` text nullable,
  `og_image` string nullable, `published_at` timestamp nullable, `author_id` foreignId users nullable, timestamps.
  Cast `content` → `array`, `status` → `PageStatus`.
- `media`: `disk` string, `path` string, `filename` string, `mime` string, `size` int, `alt` string nullable,
  `uploaded_by` foreignId users nullable, timestamps.
- `navigation_items`: `location` string (`header|footer`), `label` string, `url` string, `sort` int default 0,
  `page_id` foreignId pages nullable nullOnDelete, timestamps.

## Step 2 — Tests to write (RED)

`tests/Feature/Cms/PageManagementTest.php`:
- `it('lets an admin create a draft page')`
- `it('forbids non-admins from managing pages')` (403)
- `it('does not show draft pages publicly')` — GET `/{slug}` for a draft → 404.
- `it('shows a published page at its slug')` — 200 + Inertia `public/page`.
- `it('enforces unique slugs')`
- `it('rejects reserved slugs')` (e.g. `projects`, `admin` — per Q4)
- `it('renders seo metadata for a published page')` — assert the prop carrying SEO is present.
- (If Q2 = yes) `it('serves the home page from a published home cms page')`.

`tests/Feature/Cms/MediaTest.php`:
- `it('lets an admin upload media')` — `UploadedFile::fake()->image('x.jpg')`, assert stored + row created.
- `it('rejects oversized or wrong-mime uploads')`.

## Step 3 — Backend

`PageService`:
```php
public function create/update(array $data): Page;   // validates slug uniqueness + reserved list
public function publish(Page $page): void;          // status=Published, published_at=now()
public function unpublish(Page $page): void;
```
Reserved-slug guard: a `config/cms.php` `reserved` array (`projects`, `admin`, `settings`, `support`, `help`,
`login`, `register`, `dashboard`, `up`, `storage`, `api`). `StorePageRequest`/`UpdatePageRequest` validate
`slug` is unique, kebab-case, and not reserved.

Admin routes (add to `routes/admin.php`):
```php
Route::resource('pages', AdminPageController::class)->except(['show']);
Route::post('/pages/{page}/publish', [AdminPageController::class, 'publish'])->name('pages.publish');
Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
Route::post('/media', [AdminMediaController::class, 'store'])->name('media.store');
Route::delete('/media/{medium}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
Route::resource('navigation', AdminNavigationController::class)->only(['index','store','update','destroy']);
```
Controllers thin → call `PageService`. Media stored via `Storage::disk('public')`; run `php artisan storage:link`.

**Public rendering** (register LAST, after every other route, in `web.php`):
```php
Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('public.page');
```
`PublicPageController::show(string $slug)` → find a `published` page by slug or `abort(404)`; return Inertia
`public/page` with the page + its blocks + SEO. For `/` (Q2): a `HomeController` (or the same controller)
resolves the `home` slug, falling back to `welcome.tsx` if none is published.

## Step 4 — Frontend

- `resources/js/layouts/public-layout.tsx` — public header/footer built from `navigation_items` (no app
  sidebar). Accessible landmark elements (`<header>`, `<nav>`, `<main>`, `<footer>`).
- `resources/js/pages/public/page.tsx` — renders `<Head>` with SEO fields, then the block array via a
  reusable `BlockRenderer` component.
- `resources/js/components/blocks/` — one small component per block type (`HeadingBlock`, `ParagraphBlock`
  using `react-markdown`, `ImageBlock`, `CtaBlock`). `BlockRenderer` maps `block.type` → component. **These
  block components are reused by Phase 8 — keep them generic.**
- Admin: `resources/js/pages/admin/pages/index.tsx` (list + status), `pages/edit.tsx` (block editor:
  add/reorder/remove blocks, publish toggle, SEO fields), `admin/media.tsx`, `admin/navigation.tsx`.

Run `php artisan wayfinder:generate` after routes.

## Done when

- [ ] Admins create/edit/publish pages with SEO; non-admins blocked (403).
- [ ] Draft pages 404 publicly; published pages render at their slug with SEO in `<Head>`.
- [ ] Reserved slugs rejected; the public catch-all never shadows app routes.
- [ ] Media upload/list/delete works with validation; navigation menus editable.
- [ ] `BlockRenderer` + block components are generic and reusable (Phase 8 will reuse them).
- [ ] Full suite green, Pint clean, Wayfinder regenerated, PR opened noting Q1–Q4 answers.
