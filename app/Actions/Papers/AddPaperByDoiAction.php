<?php

namespace App\Actions\Papers;

use App\Models\Paper;
use App\Models\Project;
use App\Models\User;
use App\Services\OpenAlexSearchService;

class AddPaperByDoiAction
{
    public function __construct(
        protected OpenAlexSearchService $openAlex,
        protected SavePaperToProjectAction $savePaper,
    ) {}

    /**
     * Resolve a DOI via OpenAlex and attach the paper to the project.
     *
     * @throws \RuntimeException When the DOI cannot be resolved.
     */
    public function handle(Project $project, string $doi, ?User $user = null): Paper
    {
        $work = $this->openAlex->searchByDoi($doi, $user);

        if ($work === null) {
            throw new \RuntimeException("Could not resolve DOI: {$doi}");
        }

        return $this->savePaper->handle($project, $work);
    }
}
