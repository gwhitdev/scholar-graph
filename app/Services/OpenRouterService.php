<?php

namespace App\Services;

use App\Exceptions\OpenRouterException;
use App\Exceptions\OpenRouterTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenRouterService
{
    public function __construct(
        protected string $apiKey,
        protected string $model,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     *
     * @throws OpenRouterException
     * @throws OpenRouterTimeoutException
     */
    public function chat(array $messages, ?string $model = null): string
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->retry(1, 500)
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model ?? $this->model,
                    'messages' => $messages,
                ]);
        } catch (ConnectionException $e) {
            throw new OpenRouterTimeoutException('OpenRouter request timed out.', previous: $e);
        }

        if (! $response->successful()) {
            throw new OpenRouterException('OpenRouter request failed: '.$response->body());
        }

        return $response->json('choices.0.message.content') ?? '';
    }
}
