<?php

namespace App\Services;

use App\Actions\Usage\LogLlmCallAction;
use App\Enums\MessageRole;
use App\Exceptions\OpenRouterException;
use App\Exceptions\OpenRouterTimeoutException;
use App\Models\ChatMessage;
use App\Models\Paper;
use App\Models\Project;
use App\Models\Synthesis;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SynthesisService
{
    /**
     * The default response guidelines appended to the system prompt when no custom prompt is set.
     */
    public const DEFAULT_RESPONSE_GUIDELINES = <<<'GUIDELINES'
You are a precise, scholarly research assistant. Use ONLY the papers provided below to answer the user's question.

## Response Guidelines
- Be concise and direct. Avoid hedging, apologies, or filler phrases.
- Structure your response with clear headings, bullet points, or numbered lists where appropriate.
- Use blank lines (double newlines) between paragraphs and sections for proper formatting. Never use single newlines to separate paragraphs.
- When referencing a paper, cite it inline as (Author, Year) — e.g., (Seligman & Csikszentmihalyi, 2000).
- If the papers lack bibliographic details (authors, year, DOI), state what is known from the available metadata and note any gaps.
- End with a short "## References" section listing the full citation details of papers cited.
- Do NOT fabricate citations or metadata not present in the provided papers.
- If the question cannot be answered from the provided papers, say so clearly and explain what is missing.
GUIDELINES;

    public function __construct(
        protected OpenRouterService $openRouter,
        protected LogLlmCallAction $logLlmCall,
    ) {}

    /**
     * @return array{papers: Collection<int, Paper>, messages: Collection<int, ChatMessage>}
     */
    public function buildContext(Project $project): array
    {
        return [
            'papers' => $project->papers()->latest('added_at')->get(),
            'messages' => $project->chatMessages()->oldest()->limit(20)->get(),
        ];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public function buildPromptMessages(Project $project, string $question): array
    {
        $context = $this->buildContext($project);

        $paperContext = $context['papers']->map(function ($paper) {
            $parts = ["Title: {$paper->title}"];

            $authors = $this->extractAuthors($paper);
            if ($authors !== '') {
                $parts[] = "Authors: {$authors}";
            }

            if ($paper->year) {
                $parts[] = "Year: {$paper->year}";
            }

            $doi = $this->extractDoi($paper);
            if ($doi !== '') {
                $parts[] = "DOI: {$doi}";
            }

            $parts[] = 'Abstract: '.($paper->abstract ?? 'No abstract available');

            return implode("\n", $parts);
        })->implode("\n\n");

        $systemPrompt = $this->resolveSystemPrompt($project, $paperContext);

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
        ];

        foreach ($context['messages'] as $chatMessage) {
            $messages[] = [
                'role' => $chatMessage->role->value,
                'content' => $chatMessage->content,
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $question,
        ];

        return $messages;
    }

    /**
     * Resolve the effective system prompt based on project and user settings.
     * Composes global + project prompts (both can be used together) and appends negative prompts.
     */
    public function resolveSystemPrompt(Project $project, string $paperContext = ''): string
    {
        $promptParts = [];

        // Compose global and project prompts (both can be used together)
        if ($project->use_global_prompt && $project->user->global_system_prompt) {
            $promptParts[] = $project->user->global_system_prompt;
        }

        if ($project->system_prompt) {
            $promptParts[] = $project->system_prompt;
        }

        // Build the custom prompt from combined parts
        $customPrompt = ! empty($promptParts) ? implode("\n\n", $promptParts) : null;

        // Build negative prompt
        $negativePrompt = $this->buildNegativePrompt($project);

        if (! $paperContext) {
            if ($customPrompt) {
                $result = $customPrompt."\n\nNo papers are available. Answer the user's question using general knowledge and note when specific sources would strengthen the answer.";
            } else {
                $result = "You are a research assistant. No papers are available. Answer the user's question using general knowledge and note when specific sources would strengthen the answer.";
            }
        } elseif ($customPrompt) {
            $result = "{$customPrompt}\n\n## Available Papers\n{$paperContext}";
        } else {
            $result = self::DEFAULT_RESPONSE_GUIDELINES."\n\n## Available Papers\n{$paperContext}";
        }

        // Append negative prompt if present
        if ($negativePrompt) {
            $result .= "\n\n{$negativePrompt}";
        }

        return $result;
    }

    /**
     * Build the negative prompt section from global and project settings.
     */
    protected function buildNegativePrompt(Project $project): string
    {
        $negativeParts = [];

        if ($project->use_global_prompt && $project->user->global_negative_prompt) {
            $negativeParts[] = $project->user->global_negative_prompt;
        }

        if ($project->negative_prompt) {
            $negativeParts[] = $project->negative_prompt;
        }

        if (empty($negativeParts)) {
            return '';
        }

        return "## Do NOT\n".implode("\n", $negativeParts);
    }

    /**
     * Extract authors string from the paper's authors column or return empty string.
     */
    protected function extractAuthors(Paper $paper): string
    {
        if (empty($paper->authors)) {
            return '';
        }

        return implode(', ', $paper->authors);
    }

    /**
     * Extract DOI from the paper's doi column or return empty string.
     */
    protected function extractDoi(Paper $paper): string
    {
        return $paper->doi ?? '';
    }

    /**
     * @throws OpenRouterException
     * @throws OpenRouterTimeoutException
     */
    public function synthesise(Project $project, string $question): Synthesis
    {
        return DB::transaction(function () use ($project, $question) {
            $paperIds = $project->papers()->pluck('papers.id')->all();

            $synthesis = $project->syntheses()->create([
                'paper_ids' => $paperIds,
                'question' => $question,
                'answer' => '',
                'model_used' => config('services.openrouter.model'),
            ]);

            $project->chatMessages()->create([
                'synthesis_id' => $synthesis->id,
                'role' => MessageRole::User,
                'content' => $question,
            ]);

            $messages = $this->buildPromptMessages($project, $question);
            $result = $this->openRouter->chat($messages, user: $project->user);

            $synthesis->update(['answer' => $result->content]);

            $project->chatMessages()->create([
                'synthesis_id' => $synthesis->id,
                'role' => MessageRole::Assistant,
                'content' => $result->content,
            ]);

            $this->logLlmCall->handle(
                result: $result,
                user: $project->user,
                contextType: 'synthesis',
                contextId: $synthesis->id,
                prompt: json_encode($messages),
            );

            return $synthesis->fresh();
        });
    }
}
