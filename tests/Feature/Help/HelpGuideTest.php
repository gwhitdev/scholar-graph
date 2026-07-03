<?php

use App\Enums\PageStatus;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create();
});

// --- Authentication ---

it('requires auth to view help', function () {
    $this->get(route('help.index'))
        ->assertRedirect(route('login'));
});

// --- User-facing browsing ---

it('lists published articles grouped by category', function () {
    $category = HelpCategory::factory()->create(['title' => 'Getting Started']);
    $published = HelpArticle::factory()->published()->for($category)->create(['title' => 'Welcome']);
    $draft = HelpArticle::factory()->for($category)->create(['title' => 'Secret Draft']);

    $this->actingAs($this->user)
        ->get(route('help.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('help/index')
            ->has('categories')
        );
});

it('shows a published article', function () {
    $category = HelpCategory::factory()->create();
    $article = HelpArticle::factory()->published()->for($category)->create();

    $this->actingAs($this->user)
        ->get(route('help.show', [$category, $article]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('help/show')
            ->has('article')
            ->has('category')
        );
});

it('hides draft articles from users', function () {
    $category = HelpCategory::factory()->create();
    $draft = HelpArticle::factory()->for($category)->create(); // Draft by default

    $this->actingAs($this->user)
        ->get(route('help.show', [$category, $draft]))
        ->assertNotFound();
});

it('allows admins to preview draft articles', function () {
    $category = HelpCategory::factory()->create();
    $draft = HelpArticle::factory()->for($category)->create();

    $this->actingAs($this->admin)
        ->get(route('help.show', [$category, $draft]))
        ->assertOk();
});

// --- Search ---

it('returns search matches from title', function () {
    $category = HelpCategory::factory()->create();
    $article = HelpArticle::factory()->published()->for($category)->create([
        'title' => 'How to create a project',
    ]);

    $this->actingAs($this->user)
        ->get(route('help.search', ['q' => 'create a project']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('help/index')
            ->has('articles')
        );
});

it('returns search matches from content body', function () {
    $category = HelpCategory::factory()->create();
    $article = HelpArticle::factory()->published()->for($category)->create([
        'title' => 'Random Title',
        'content' => [
            ['type' => 'paragraph', 'text' => 'This article covers synthesising research papers.'],
        ],
    ]);

    $this->actingAs($this->user)
        ->get(route('help.search', ['q' => 'synthesising']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('help/index')
            ->has('articles')
        );
});

// --- Admin: Categories ---

it('lets an admin create a help category', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.help-categories.store'), [
            'slug' => 'getting-started',
            'title' => 'Getting Started',
            'sort' => 0,
        ])
        ->assertRedirect(route('admin.help-categories.index'));

    expect(HelpCategory::where('slug', 'getting-started')->first())
        ->not->toBeNull();
});

it('lets an admin update a help category', function () {
    $category = HelpCategory::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.help-categories.update', $category), [
            'slug' => $category->slug,
            'title' => 'Updated Category Title',
            'sort' => 5,
        ])
        ->assertRedirect(route('admin.help-categories.index'));

    expect($category->fresh()->title)->toBe('Updated Category Title')
        ->and($category->fresh()->sort)->toBe(5);
});

it('lets an admin delete a help category', function () {
    $category = HelpCategory::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.help-categories.destroy', $category))
        ->assertRedirect(route('admin.help-categories.index'));

    expect(HelpCategory::find($category->id))->toBeNull();
});

it('enforces unique category slugs', function () {
    HelpCategory::factory()->create(['slug' => 'unique-cat']);

    $this->actingAs($this->admin)
        ->post(route('admin.help-categories.store'), [
            'slug' => 'unique-cat',
            'title' => 'Duplicate',
        ])
        ->assertSessionHasErrors('slug');
});

// --- Admin: Articles ---

it('lets an admin create and publish an article', function () {
    $category = HelpCategory::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.help-articles.store'), [
            'help_category_id' => $category->id,
            'slug' => 'welcome-guide',
            'title' => 'Welcome Guide',
            'content' => [
                ['type' => 'heading', 'level' => 1, 'text' => 'Welcome'],
                ['type' => 'paragraph', 'text' => 'This is a guide.'],
            ],
            'sort' => 0,
        ])
        ->assertRedirect(route('admin.help-articles.index'));

    $article = HelpArticle::where('slug', 'welcome-guide')->first();
    expect($article)->not->toBeNull()
        ->and($article->status)->toBe(PageStatus::Draft);

    // Publish it
    $this->actingAs($this->admin)
        ->post(route('admin.help-articles.publish', $article))
        ->assertRedirect();

    expect($article->fresh()->status)->toBe(PageStatus::Published);
});

it('lets an admin unpublish an article', function () {
    $article = HelpArticle::factory()->published()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.help-articles.unpublish', $article))
        ->assertRedirect();

    expect($article->fresh()->status)->toBe(PageStatus::Draft);
});

it('lets an admin update an article', function () {
    $article = HelpArticle::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.help-articles.update', $article), [
            'help_category_id' => $article->help_category_id,
            'slug' => $article->slug,
            'title' => 'Updated Article Title',
            'content' => [['type' => 'paragraph', 'text' => 'Updated content']],
            'sort' => 0,
        ])
        ->assertRedirect(route('admin.help-articles.index'));

    expect($article->fresh()->title)->toBe('Updated Article Title');
});

it('lets an admin delete an article', function () {
    $article = HelpArticle::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.help-articles.destroy', $article))
        ->assertRedirect(route('admin.help-articles.index'));

    expect(HelpArticle::find($article->id))->toBeNull();
});

// --- Authorization ---

it('forbids non-admins from managing help', function () {
    $category = HelpCategory::factory()->create();
    $article = HelpArticle::factory()->create();

    // Category management forbidden
    $this->actingAs($this->user)
        ->get(route('admin.help-categories.index'))
        ->assertForbidden();

    $this->actingAs($this->user)
        ->post(route('admin.help-categories.store'), [
            'slug' => 'hacked',
            'title' => 'Hacked',
        ])
        ->assertForbidden();

    // Article management forbidden
    $this->actingAs($this->user)
        ->get(route('admin.help-articles.index'))
        ->assertForbidden();

    $this->actingAs($this->user)
        ->post(route('admin.help-articles.store'), [
            'help_category_id' => $category->id,
            'slug' => 'hacked-article',
            'title' => 'Hacked',
        ])
        ->assertForbidden();

    $this->actingAs($this->user)
        ->put(route('admin.help-articles.update', $article), [
            'help_category_id' => $category->id,
            'slug' => $article->slug,
            'title' => 'Hacked',
        ])
        ->assertForbidden();

    $this->actingAs($this->user)
        ->delete(route('admin.help-articles.destroy', $article))
        ->assertForbidden();
});
