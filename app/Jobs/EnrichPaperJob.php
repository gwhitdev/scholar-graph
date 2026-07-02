<?php

namespace App\Jobs;

use App\Actions\Papers\GeneratePaperSummaryAction;
use App\Models\Paper;
use App\Services\SemanticScholarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class EnrichPaperJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    /**
     * Releases count as attempts, so allow a few S2 retries before giving up.
     */
    public int $tries = 3;

    public function __construct(
        public Paper $paper,
    ) {}

    /**
     * Execute the job.
     *
     * Prefers Semantic Scholar's TLDR (grounded in the full paper text) and
     * falls back to generating a summary from the abstract with the LLM, so a
     * rate-limited or empty S2 response never leaves the user waiting.
     */
    public function handle(SemanticScholarService $semanticScholar, GeneratePaperSummaryAction $generateSummary): void
    {
        if ($this->paper->enrichment?->enriched_at) {
            return;
        }

        $s2Data = $this->paper->doi
            ? $semanticScholar->enrich($this->paper->doi)
            : null;

        $tldr = $s2Data['tldr'] ?? null;
        $tldrSource = $tldr !== null ? 'semantic_scholar' : null;

        if ($tldr === null) {
            $tldr = $generateSummary->handle($this->paper);
            $tldrSource = $tldr !== null ? 'generated' : null;
        }

        // Nothing to store and S2 may recover: retry later. Without a DOI
        // there is nothing left to wait for, so give up quietly.
        if ($tldr === null && $s2Data === null) {
            if ($this->paper->doi) {
                $this->release(300);
            }

            return;
        }

        $this->paper->enrichment()->updateOrCreate(
            ['paper_id' => $this->paper->id],
            [
                'tldr' => $tldr,
                'tldr_source' => $tldrSource,
                'influential_citation_count' => $s2Data['influential_citation_count'] ?? null,
                'enriched_at' => now(),
            ]
        );
    }
}
