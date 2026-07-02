<?php

namespace App\Http\Controllers;

use App\Actions\Projects\CreateProjectAction;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $projects = $request->user()->projects()->latest()->get();

        return Inertia::render('projects/index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request, CreateProjectAction $action): RedirectResponse
    {
        $project = $action->handle($request->user(), $request->validated('name'));

        return to_route('projects.show', $project);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $project->load('user');

        return Inertia::render('projects/show', [
            'project' => $project,
            'papers' => $project->papers()->with('enrichment')->latest('added_at')->get(),
            'chatMessages' => $project->chatMessages()
                ->with('synthesis')
                ->oldest()
                ->get(),
            'syntheses' => $project->syntheses()->latest()->get(),
            'globalSystemPrompt' => $request->user()->global_system_prompt,
            'globalNegativePrompt' => $request->user()->global_negative_prompt,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return to_route('projects.index');
    }
}
