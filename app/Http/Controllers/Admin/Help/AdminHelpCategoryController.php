<?php

namespace App\Http\Controllers\Admin\Help;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHelpCategoryRequest;
use App\Http\Requests\UpdateHelpCategoryRequest;
use App\Models\HelpCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminHelpCategoryController extends Controller
{
    /**
     * Display a listing of help categories.
     */
    public function index(): Response
    {
        $categories = HelpCategory::withCount('articles')->orderBy('sort')->get();

        return Inertia::render('admin/help/categories/index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): Response
    {
        return Inertia::render('admin/help/categories/create');
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreHelpCategoryRequest $request): RedirectResponse
    {
        HelpCategory::create($request->validated());

        return redirect()->route('admin.help-categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(HelpCategory $helpCategory): Response
    {
        return Inertia::render('admin/help/categories/edit', [
            'category' => $helpCategory,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateHelpCategoryRequest $request, HelpCategory $helpCategory): RedirectResponse
    {
        $helpCategory->update($request->validated());

        return redirect()->route('admin.help-categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(HelpCategory $helpCategory): RedirectResponse
    {
        $helpCategory->delete();

        return redirect()->route('admin.help-categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
