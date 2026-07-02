<?php

namespace App\Services;

use App\Exceptions\OpenRouterException;
use App\Exceptions\OpenRouterTimeoutException;
use App\Models\User;
use App\Services\DTO\ChatResult;
use App\Support\ApiUsageRecorder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenRouterService
{
    public function __construct(
        protected string $apiKey,
        protected string $model,
        protected string $baseUrl = 'https://openrouter.ai/api/v1',
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     *
     * @throws OpenRouterException
     * @throws OpenRouterTimeoutException
     */
    public function chat(array $messages, ?string $model = null, ?User $user = null): ChatResult
    {
        $usedModel = $model ?? $this->model;
        $start = microtime(true);

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->retry(1, 500)
                ->post($this->baseUrl.'/chat/completions', [
                    'model' => $usedModel,
                    'messages' => $messages,
                    'usage' => ['include' => true],
                ]);
        } catch (ConnectionException $e) {
            throw new OpenRouterTimeoutException('OpenRouter request timed out.', previous: $e);
        }

        if (! $response->successful()) {
            throw new OpenRouterException('OpenRouter request failed: '.$response->body());
        }

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        ApiUsageRecorder::record(
            service: 'openrouter',
            endpoint: '/chat/completions',
            status: $response->status(),
            durationMs: $durationMs,
            user: $user,
            method: 'POST',
        );

        return new ChatResult(
            content: $response->json('choices.0.message.content') ?? '',
            model: $usedModel,
            promptTokens: $response->json('usage.prompt_tokens'),
            completionTokens: $response->json('usage.completion_tokens'),
            costUsd: $response->json('usage.cost'),
            durationMs: $durationMs,
        );
    }
}
