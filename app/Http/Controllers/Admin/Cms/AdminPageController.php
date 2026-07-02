<?php

namespace App\Http\Controllers\Admin\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Page;
use App\Services\Cms\PageService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminPageController extends Controller
{
    public function __construct(private PageService $pageService) {}

    /**
     * Display a listing of pages.
     */
    public function index(): Response
    {
        $pages = Page::with('author')->latest()->paginate(20);

        return Inertia::render('admin/pages/index', [
            'pages' => $pages,
        ]);
    }

    /**
     * Show the form for creating a new page.
     */
    public function create(): Response
    {
        return Inertia::render('admin/pages/create');
    }

    /**
     * Store a newly created page.
     */
    public function store(StorePageRequest $request): RedirectResponse
    {
        $this->pageService->create(array_merge(
            $request->validated(),
            ['author_id' => $request->user()->id]
        ));

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page created successfully.');
    }

    /**
     * Show the form for editing a page.
     */
    public function edit(Page $page): Response
    {
        return Inertia::render('admin/pages/edit', [
            'page' => $page,
        ]);
    }

    /**
     * Update the specified page.
     */
    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $this->pageService->update($page, $request->validated());

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page updated successfully.');
    }

    /**
     * Remove the specified page.
     */
    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.pages.index')
            ->with('success', 'Page deleted successfully.');
    }

    /**
     * Publish a page.
     */
    public function publish(Page $page): RedirectResponse
    {
        $this->pageService->publish($page);

        return back()->with('success', 'Page published.');
    }

    /**
     * Unpublish a page.
     */
    public function unpublish(Page $page): RedirectResponse
    {
        $this->pageService->unpublish($page);

        return back()->with('success', 'Page unpublished.');
    }
}
