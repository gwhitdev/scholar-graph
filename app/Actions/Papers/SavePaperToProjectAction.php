<?php

namespace App\Actions\Papers;

use App\Jobs\EnrichPaperJob;
use App\Models\Paper;
use App\Models\Project;

class SavePaperToProjectAction
{
    /**
     * Save a paper to the given project and queue its enrichment.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Project $project, array $data): Paper
    {
        $paper = Paper::firstOrCreate(
            [
                'project_id' => $project->id,
                'openalex_id' => $data['openalex_id'] ?? null,
            ],
            $data
        );

        if ($paper->wasRecentlyCreated) {
            EnrichPaperJob::dispatch($paper);
        }

        return $paper;
    }
}
