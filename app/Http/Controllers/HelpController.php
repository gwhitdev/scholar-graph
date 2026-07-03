<?php

namespace App\Http\Controllers;

use App\Enums\PageStatus;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    /**
     * Display the help centre index with categories and published articles.
     */
    public function index(): Response
    {
        $categories = HelpCategory::with(['articles' => function ($query) {
            $query->where('status', PageStatus::Published)->orderBy('sort');
        }])
            ->orderBy('sort')
            ->get();

        return Inertia::render('help/index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Search published help articles by title or content.
     */
    public function search(Request $request): Response
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = $request->string('q', '')->trim()->toString();
        $categories = HelpCategory::orderBy('sort')->get();

        $articles = collect();

        if ($query !== '') {
            $searchTerm = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $query).'%';

            $articles = HelpArticle::where('status', PageStatus::Published)
                ->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', $searchTerm)
                        ->orWhereRaw('CAST(content AS TEXT) LIKE ?', [$searchTerm]);
                })
                ->with('helpCategory')
                ->orderBy('sort')
                ->get();
        }

        return Inertia::render('help/index', [
            'categories' => $categories,
            'articles' => $articles,
            'search' => $query,
        ]);
    }

    /**
     * Show a single help article.
     */
    public function show(HelpCategory $category, HelpArticle $article): Response
    {
        $user = auth()->user();

        // Non-admins cannot view draft articles
        if (! $article->isPublished() && ! ($user && $user->isAdmin())) {
            abort(404);
        }

        return Inertia::render('help/show', [
            'category' => $category,
            'article' => $article,
        ]);
    }
}
