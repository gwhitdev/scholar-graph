<?php

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create();
});

it('lets an admin create a draft page', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.pages.store'), [
            'slug' => 'about-us',
            'title' => 'About Us',
            'content' => [
                ['type' => 'heading', 'level' => 1, 'text' => 'About Us'],
                ['type' => 'paragraph', 'text' => 'Welcome to our site.'],
            ],
            'seo_title' => 'About ScholarGraph',
            'seo_description' => 'Learn more about us.',
        ])
        ->assertRedirect(route('admin.pages.index'));

    expect(Page::where('slug', 'about-us')->first())
        ->not->toBeNull()
        ->and(Page::where('slug', 'about-us')->first()->status)
        ->toBe(PageStatus::Draft);
});

it('lets an admin update a page', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.pages.update', $page), [
            'slug' => $page->slug,
            'title' => 'Updated Title',
            'content' => [['type' => 'paragraph', 'text' => 'Updated']],
            'seo_title' => 'Updated SEO',
            'seo_description' => 'Updated description',
        ])
        ->assertRedirect(route('admin.pages.index'));

    expect($page->fresh()->title)->toBe('Updated Title');
});

it('lets an admin delete a page', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.pages.destroy', $page))
        ->assertRedirect(route('admin.pages.index'));

    expect(Page::find($page->id))->toBeNull();
});

it('lets an admin publish a page', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.pages.publish', $page))
        ->assertRedirect();

    expect($page->fresh()->status)->toBe(PageStatus::Published)
        ->and($page->fresh()->published_at)->not->toBeNull();
});

it('lets an admin unpublish a page', function () {
    $page = Page::factory()->published()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.pages.unpublish', $page))
        ->assertRedirect();

    expect($page->fresh()->status)->toBe(PageStatus::Draft)
        ->and($page->fresh()->published_at)->toBeNull();
});

it('forbids non-admins from managing pages', function () {
    $page = Page::factory()->create();

    $this->actingAs($this->user)
        ->get(route('admin.pages.index'))
        ->assertForbidden();

    $this->actingAs($this->user)
        ->post(route('admin.pages.store'), [
            'slug' => 'test',
            'title' => 'Test',
        ])
        ->assertForbidden();

    $this->actingAs($this->user)
        ->put(route('admin.pages.update', $page), [
            'slug' => $page->slug,
            'title' => 'Hacked',
        ])
        ->assertForbidden();

    $this->actingAs($this->user)
        ->delete(route('admin.pages.destroy', $page))
        ->assertForbidden();
});

it('enforces unique slugs', function () {
    Page::factory()->slug('unique-slug')->create();

    $this->actingAs($this->admin)
        ->post(route('admin.pages.store'), [
            'slug' => 'unique-slug',
            'title' => 'Duplicate',
        ])
        ->assertSessionHasErrors('slug');
});

it('rejects reserved slugs', function (string $reservedSlug) {
    $this->actingAs($this->admin)
        ->post(route('admin.pages.store'), [
            'slug' => $reservedSlug,
            'title' => 'Reserved Test',
        ])
        ->assertSessionHasErrors('slug');
})->with([
    'projects',
    'admin',
    'settings',
    'login',
    'register',
    'api',
]);

it('does not show draft pages publicly', function () {
    $page = Page::factory()->create(); // Draft by default

    $this->get(route('public.page', ['slug' => $page->slug]))
        ->assertNotFound();
});

it('shows a published page at its slug', function () {
    $page = Page::factory()->published()->create();

    $this->get(route('public.page', ['slug' => $page->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/page')
            ->has('page')
        );
});

it('renders SEO metadata for a published page', function () {
    $page = Page::factory()->published()->create([
        'seo_title' => 'My Custom SEO Title',
        'seo_description' => 'My custom SEO description.',
    ]);

    $this->get(route('public.page', ['slug' => $page->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert
            ->where('page.seo_title', 'My Custom SEO Title')
            ->where('page.seo_description', 'My custom SEO description.')
        );
});

it('returns 404 for unknown slugs', function () {
    $this->get(route('public.page', ['slug' => 'nonexistent-page']))
        ->assertNotFound();
});

it('does not shadow app routes with the public catch-all', function () {
    $this->actingAs($this->user)
        ->get('/projects')
        ->assertOk();
});
