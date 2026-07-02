<?php

namespace App\Http\Controllers;

use App\Enums\PageStatus;
use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    /**
     * Show a published page by its slug.
     */
    public function show(string $slug): Response
    {
        $page = Page::where('slug', $slug)
            ->where('status', PageStatus::Published)
            ->firstOrFail();

        return Inertia::render('public/page', [
            'page' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
                'seo_title' => $page->seo_title,
                'seo_description' => $page->seo_description,
                'og_image' => $page->og_image,
            ],
        ]);
    }
}
