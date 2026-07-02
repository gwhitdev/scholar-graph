<?php

namespace App\Actions\Papers;

use App\Exceptions\OpenRouterException;
use App\Exceptions\OpenRouterTimeoutException;
use App\Models\Paper;
use App\Services\OpenRouterService;

class GeneratePaperSummaryAction
{
    public function __construct(
        protected OpenRouterService $llm,
    ) {}

    /**
     * Generate a short TLDR summary of a paper from its abstract.
     *
     * Returns null when the paper has no abstract or the LLM call fails, so
     * callers can treat generation as best-effort.
     */
    public function handle(Paper $paper): ?string
    {
        if (! $paper->abstract) {
            return null;
        }

        try {
            $summary = $this->llm->chat([
                [
                    'role' => 'system',
                    'content' => 'You write one-to-two sentence TLDR summaries of academic papers based on their title and abstract. Respond with only the summary text, no preamble.',
                ],
                [
                    'role' => 'user',
                    'content' => "Title: {$paper->title}\n\nAbstract: {$paper->abstract}",
                ],
            ]);
        } catch (OpenRouterException|OpenRouterTimeoutException) {
            return null;
        }

        return $summary !== '' ? $summary : null;
    }
}
