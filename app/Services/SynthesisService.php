<?php

namespace App\Services;

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
    public function __construct(
        protected OpenRouterService $openRouter,
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
            return "Title: {$paper->title}\nAbstract: ".($paper->abstract ?? 'No abstract available');
        })->implode("\n\n");

        $messages = [
            [
                'role' => 'system',
                'content' => "You are a research assistant. Use the following papers to answer the user's questions:\n\n".($paperContext ?: 'No papers available.'),
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
            $answer = $this->openRouter->chat($messages);

            $synthesis->update(['answer' => $answer]);

            $project->chatMessages()->create([
                'synthesis_id' => $synthesis->id,
                'role' => MessageRole::Assistant,
                'content' => $answer,
            ]);

            return $synthesis->fresh();
        });
    }
}
