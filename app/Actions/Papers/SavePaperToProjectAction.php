<?php

namespace App\Actions\Papers;

use App\Enums\PaperStatus;
use App\Jobs\EnrichPaperJob;
use App\Models\Paper;
use App\Models\Project;

class SavePaperToProjectAction
{
    /**
     * Save a paper to the given project and queue its enrichment.
     *
     * The paper itself is canonical (shared across all users). It is deduplicated
     * by openalex_id when present, otherwise by doi. The project link is stored on
     * the project_papers pivot, including per-project status.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Project $project, array $data): Paper
    {
        $paperAttributes = [
            'title' => $data['title'] ?? null,
            'abstract' => $data['abstract'] ?? null,
            'year' => $data['year'] ?? null,
            'authors' => $data['authors'] ?? null,
            'doi' => $data['doi'] ?? null,
            'venue' => $data['venue'] ?? null,
            'cited_by_count' => $data['cited_by_count'] ?? null,
            'referenced_works' => $data['referenced_works'] ?? null,
        ];

        if (! empty($data['openalex_id'])) {
            $paper = Paper::firstOrCreate(
                ['openalex_id' => $data['openalex_id']],
                $paperAttributes,
            );
        } elseif (! empty($data['doi'])) {
            $paper = Paper::firstOrCreate(
                ['doi' => $data['doi']],
                $paperAttributes,
            );
        } else {
            $paper = Paper::create($paperAttributes);
        }

        $project->papers()->syncWithoutDetaching([
            $paper->id => [
                'user_id' => $project->user_id,
                'status' => PaperStatus::Unread->value,
                'added_at' => now(),
            ],
        ]);

        if ($paper->wasRecentlyCreated) {
            EnrichPaperJob::dispatch($paper, $project->user);
        }

        return $paper;
    }
}
