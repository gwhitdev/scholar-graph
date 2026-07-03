<?php

namespace App\Services\Help;

use App\Enums\PageStatus;
use App\Models\HelpArticle;
use Illuminate\Support\Arr;

class HelpArticleService
{
    /**
     * Create a new help article.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): HelpArticle
    {
        return HelpArticle::create([
            'help_category_id' => $data['help_category_id'],
            'slug' => $data['slug'],
            'title' => $data['title'],
            'content' => Arr::get($data, 'content'),
            'status' => PageStatus::Draft,
            'sort' => Arr::get($data, 'sort', 0),
        ]);
    }

    /**
     * Update an existing help article.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(HelpArticle $article, array $data): HelpArticle
    {
        $article->update([
            'help_category_id' => $data['help_category_id'],
            'slug' => $data['slug'],
            'title' => $data['title'],
            'content' => Arr::get($data, 'content'),
            'sort' => Arr::get($data, 'sort', 0),
        ]);

        return $article->fresh();
    }

    /**
     * Publish a help article.
     */
    public function publish(HelpArticle $article): void
    {
        $article->publish();
    }

    /**
     * Unpublish a help article.
     */
    public function unpublish(HelpArticle $article): void
    {
        $article->unpublish();
    }
}
