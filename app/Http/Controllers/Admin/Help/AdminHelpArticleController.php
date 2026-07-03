<?php

namespace App\Http\Controllers\Admin\Help;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHelpArticleRequest;
use App\Http\Requests\UpdateHelpArticleRequest;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Services\Help\HelpArticleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminHelpArticleController extends Controller
{
    public function __construct(private HelpArticleService $articleService) {}

    /**
     * Display a listing of help articles.
     */
    public function index(): Response
    {
        $articles = HelpArticle::with('helpCategory')->orderBy('sort')->get();

        return Inertia::render('admin/help/articles/index', [
            'articles' => $articles,
        ]);
    }

    /**
     * Show the form for creating a new article.
     */
    public function create(): Response
    {
        $categories = HelpCategory::orderBy('sort')->get();

        return Inertia::render('admin/help/articles/create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created article.
     */
    public function store(StoreHelpArticleRequest $request): RedirectResponse
    {
        $this->articleService->create($request->validated());

        return redirect()->route('admin.help-articles.index')
            ->with('success', 'Article created successfully.');
    }

    /**
     * Show the form for editing an article.
     */
    public function edit(HelpArticle $helpArticle): Response
    {
        $categories = HelpCategory::orderBy('sort')->get();

        return Inertia::render('admin/help/articles/edit', [
            'article' => $helpArticle,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified article.
     */
    public function update(UpdateHelpArticleRequest $request, HelpArticle $helpArticle): RedirectResponse
    {
        $this->articleService->update($helpArticle, $request->validated());

        return redirect()->route('admin.help-articles.index')
            ->with('success', 'Article updated successfully.');
    }

    /**
     * Remove the specified article.
     */
    public function destroy(HelpArticle $helpArticle): RedirectResponse
    {
        $helpArticle->delete();

        return redirect()->route('admin.help-articles.index')
            ->with('success', 'Article deleted successfully.');
    }

    /**
     * Publish a help article.
     */
    public function publish(HelpArticle $helpArticle): RedirectResponse
    {
        $this->articleService->publish($helpArticle);

        return back()->with('success', 'Article published.');
    }

    /**
     * Unpublish a help article.
     */
    public function unpublish(HelpArticle $helpArticle): RedirectResponse
    {
        $this->articleService->unpublish($helpArticle);

        return back()->with('success', 'Article unpublished.');
    }
}
