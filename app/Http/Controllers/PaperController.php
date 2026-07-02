<?php

namespace App\Http\Controllers;

use App\Actions\Papers\SavePaperToProjectAction;
use App\Exceptions\SemanticScholarRateLimitException;
use App\Http\Requests\StorePaperRequest;
use App\Models\Paper;
use App\Models\Project;
use App\Services\SemanticScholarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaperController extends Controller
{
    public function __construct(
        protected SemanticScholarService $semanticScholar,
    ) {}

    public function search(Request $request, Project $project): JsonResponse
    {
        abort_unless($project->user_id === $request->user()->id, 403);

        try {
            $results = $this->semanticScholar->search(
                $request->query('query', ''),
                min((int) $request->query('limit', 10), 20)
            );
        } catch (SemanticScholarRateLimitException) {
            return response()->json([
                'error' => 'Search limit reached. Please try again in a few minutes.',
            ], 429);
        }

        return response()->json($results);
    }

    public function store(StorePaperRequest $request, Project $project, SavePaperToProjectAction $action): RedirectResponse
    {
        abort_unless($project->user_id === $request->user()->id, 403);

        $action->handle($project, $request->validated());

        return redirect()->back();
    }

    public function destroy(Request $request, Project $project, Paper $paper): RedirectResponse
    {
        abort_unless($project->user_id === $request->user()->id, 403);
        abort_unless($paper->project_id === $project->id, 403);

        $paper->delete();

        return redirect()->back();
    }
}
