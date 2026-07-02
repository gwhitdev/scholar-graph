<?php

namespace App\Actions\Syntheses;

use App\Exceptions\InsufficientCreditsException;
use App\Models\Project;
use App\Models\Synthesis;
use App\Services\Billing\CreditService;
use App\Services\SynthesisService;

class CreateSynthesisAction
{
    public function __construct(
        protected SynthesisService $synthesisService,
        protected CreditService $creditService,
    ) {}

    /**
     * @throws InsufficientCreditsException
     */
    public function handle(Project $project, string $question): Synthesis
    {
        $user = $project->user;

        if ($this->creditService->balance($user) <= 0) {
            throw new InsufficientCreditsException;
        }

        $synthesis = $this->synthesisService->synthesise($project, $question);

        $this->creditService->debit($user, 1, 'llm_spend', [
            'synthesis_id' => $synthesis->id,
        ]);

        return $synthesis;
    }
}
