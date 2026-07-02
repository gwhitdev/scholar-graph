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
        $this->authorize('view', $project);

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
        $this->authorize('update', $project);

        $action->handle($project, $request->validated());

        return redirect()->back();
    }

    public function enrich(Request $request, Project $project, Paper $paper): JsonResponse
    {
        $this->authorize('update', $project);

        EnrichPaperJob::dispatch($paper);

        return response()->json(['message' => 'Enrichment queued.'], 202);
    }

    public function destroy(Request $request, Project $project, Paper $paper): RedirectResponse
    {
        $this->authorize('update', $project);

        $paper->delete();

        return redirect()->back();
    }
}
