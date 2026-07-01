<?php

namespace App\Actions\Syntheses;

use App\Models\Project;
use App\Models\Synthesis;
use App\Services\SynthesisService;

class CreateSynthesisAction
{
    public function __construct(
        protected SynthesisService $synthesisService,
    ) {}

    public function handle(Project $project, string $question): Synthesis
    {
        return $this->synthesisService->synthesise($project, $question);
    }
}
