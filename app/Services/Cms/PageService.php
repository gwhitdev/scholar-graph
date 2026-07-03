<?php

namespace App\Services\Cms;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Support\Arr;

class PageService
{
    /**
     * Create a new page.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Page
    {
        return Page::create([
            'slug' => $data['slug'],
            'title' => $data['title'],
            'content' => Arr::get($data, 'content'),
            'status' => PageStatus::Draft,
            'seo_title' => Arr::get($data, 'seo_title'),
            'seo_description' => Arr::get($data, 'seo_description'),
            'og_image' => Arr::get($data, 'og_image'),
            'author_id' => Arr::get($data, 'author_id'),
        ]);
    }

    /**
     * Update an existing page.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Page $page, array $data): Page
    {
        $page->update([
            'slug' => $data['slug'],
            'title' => $data['title'],
            'content' => Arr::get($data, 'content'),
            'seo_title' => Arr::get($data, 'seo_title'),
            'seo_description' => Arr::get($data, 'seo_description'),
            'og_image' => Arr::get($data, 'og_image'),
        ]);

        return $page->fresh();
    }

    /**
     * Publish a page.
     */
    public function publish(Page $page): void
    {
        $page->publish();
    }

    /**
     * Unpublish a page.
     */
    public function unpublish(Page $page): void
    {
        $page->unpublish();
    }
}
