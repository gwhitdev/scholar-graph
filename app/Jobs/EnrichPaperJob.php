<?php

namespace App\Jobs;

use App\Models\Paper;
use App\Services\SemanticScholarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class EnrichPaperJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        public Paper $paper,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(SemanticScholarService $semanticScholar): void
    {
        if ($this->paper->enrichment?->enriched_at) {
            return;
        }

        // Semantic Scholar enrichment requires a DOI; an OpenAlex ID is not a valid S2 lookup key.
        if (! $this->paper->doi) {
            return;
        }

        $data = $semanticScholar->enrich($this->paper->doi);

        if ($data === null) {
            $this->release(300);

            return;
        }

        $this->paper->enrichment()->updateOrCreate(
            ['paper_id' => $this->paper->id],
            array_merge($data, ['enriched_at' => now()])
        );
    }
}
