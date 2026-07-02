<?php

namespace App\Http\Controllers;

use App\Actions\Papers\SavePaperToProjectAction;
use App\Http\Requests\StorePaperRequest;
use App\Jobs\EnrichPaperJob;
use App\Models\Paper;
use App\Models\Project;
use App\Services\OpenAlexSearchService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaperController extends Controller
{
    public function __construct(
        protected OpenAlexSearchService $openAlex,
    ) {}

    public function search(Request $request, Project $project): JsonResponse
    {
        abort_unless($project->user_id === $request->user()->id, 403);

        try {
            $results = $this->openAlex->search(
                $request->query('query', ''),
                min((int) $request->query('limit', 10), 20)
            );
        } catch (RequestException) {
            return response()->json([
                'error' => 'Paper search is temporarily unavailable. Please try again shortly.',
            ], 503);
        }

        return response()->json($results);
    }

    public function store(StorePaperRequest $request, Project $project, SavePaperToProjectAction $action): RedirectResponse
    {
        abort_unless($project->user_id === $request->user()->id, 403);

        $action->handle($project, $request->validated());

        return redirect()->back();
    }

    public function enrich(Request $request, Project $project, Paper $paper): JsonResponse
    {
        abort_unless($project->user_id === $request->user()->id, 403);
        abort_unless($paper->project_id === $project->id, 403);

        EnrichPaperJob::dispatch($paper);

        return response()->json(['message' => 'Enrichment queued.'], 202);
    }

    public function destroy(Request $request, Project $project, Paper $paper): RedirectResponse
    {
        abort_unless($project->user_id === $request->user()->id, 403);
        abort_unless($paper->project_id === $project->id, 403);

        $paper->delete();

        return redirect()->back();
    }
}
