<?php

namespace App\Actions\Usage;

use App\Models\LlmCall;
use App\Models\User;
use App\Services\DTO\ChatResult;

class LogLlmCallAction
{
    public function handle(
        ChatResult $result,
        ?User $user,
        string $contextType,
        ?int $contextId,
        ?string $prompt = null,
    ): LlmCall {
        return LlmCall::create([
            'user_id' => $user?->id,
            'model' => $result->model,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'prompt' => $prompt,
            'prompt_tokens' => $result->promptTokens,
            'completion_tokens' => $result->completionTokens,
            'cost_usd' => $result->costUsd,
            'duration_ms' => $result->durationMs,
            'status' => 'success',
        ]);
    }
}
