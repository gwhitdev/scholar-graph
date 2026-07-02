<?php

namespace App\Http\Controllers;

use App\Actions\Collections\AddPaperToCollectionAction;
use App\Actions\Collections\CreateCollectionAction;
use App\Http\Requests\AddPaperToCollectionRequest;
use App\Http\Requests\StoreCollectionRequest;
use App\Http\Requests\UpdateCollectionRequest;
use App\Models\Collection;
use App\Models\Paper;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CollectionController extends Controller
{
    /**
     * Display a listing of the project's collections.
     */
    public function index(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json([
            'collections' => $project->collections()->with('papers:id')->get(),
        ]);
    }

    /**
     * Store a newly created collection in the project.
     */
    public function store(StoreCollectionRequest $request, Project $project, CreateCollectionAction $action): RedirectResponse
    {
        $this->authorize('update', $project);

        $action->handle($project, $request->user(), $request->validated());

        return to_route('projects.show', $project);
    }

    /**
     * Update the specified collection.
     */
    public function update(UpdateCollectionRequest $request, Project $project, Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $collection->update($request->validated());

        return to_route('projects.show', $project);
    }

    /**
     * Remove the specified collection from storage.
     */
    public function destroy(Project $project, Collection $collection): RedirectResponse
    {
        $this->authorize('delete', $collection);

        $collection->delete();

        return to_route('projects.show', $project);
    }

    /**
     * Add a paper to the collection.
     */
    public function addPaper(AddPaperToCollectionRequest $request, Project $project, Collection $collection, AddPaperToCollectionAction $action): RedirectResponse
    {
        $this->authorize('addPaper', $collection);

        $paper = Paper::findOrFail($request->validated('paper_id'));

        $action->handle($collection, $paper);

        return to_route('projects.show', $project);
    }

    /**
     * Remove a paper from the collection.
     */
    public function removePaper(Project $project, Collection $collection, Paper $paper): RedirectResponse
    {
        $this->authorize('removePaper', $collection);

        $collection->papers()->detach($paper);

        return to_route('projects.show', $project);
    }
}
