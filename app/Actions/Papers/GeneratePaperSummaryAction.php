<?php

namespace App\Actions\Papers;

use App\Actions\Usage\LogLlmCallAction;
use App\Exceptions\OpenRouterException;
use App\Exceptions\OpenRouterTimeoutException;
use App\Models\Paper;
use App\Services\OpenRouterService;

class GeneratePaperSummaryAction
{
    public function __construct(
        protected OpenRouterService $llm,
        protected LogLlmCallAction $logLlmCall,
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

        $messages = [
            [
                'role' => 'system',
                'content' => 'You write one-to-two sentence TLDR summaries of academic papers based on their title and abstract. Respond with only the summary text, no preamble.',
            ],
            [
                'role' => 'user',
                'content' => "Title: {$paper->title}\n\nAbstract: {$paper->abstract}",
            ],
        ];

        try {
            $result = $this->llm->chat($messages, user: $paper->project->user);
        } catch (OpenRouterException|OpenRouterTimeoutException) {
            return null;
        }

        if ($result->content === '') {
            return null;
        }

        $this->logLlmCall->handle(
            result: $result,
            user: $paper->project->user,
            contextType: 'paper_summary',
            contextId: $paper->id,
            prompt: json_encode($messages),
        );

        return $result->content;
    }
}
