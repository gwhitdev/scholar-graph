<?php

namespace App\Actions\Papers;

use App\Models\Paper;
use App\Models\Project;

class SavePaperToProjectAction
{
    /**
     * Save a paper to the given project.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Project $project, array $data): Paper
    {
        return Paper::firstOrCreate(
            [
                'project_id' => $project->id,
                'semantic_scholar_id' => $data['semantic_scholar_id'] ?? null,
            ],
            $data
        );
    }
}
