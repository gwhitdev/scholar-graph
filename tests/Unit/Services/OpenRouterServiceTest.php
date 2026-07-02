<?php

use App\Exceptions\OpenRouterException;
use App\Exceptions\OpenRouterTimeoutException;
use App\Services\DTO\ChatResult;
use App\Services\OpenRouterService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

test('returns content, tokens, cost and duration', function () {
    Http::fake([
        'openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'This is the answer.',
                    ],
                ],
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
                'cost' => 0.0003,
            ],
        ], 200),
    ]);

    $service = new OpenRouterService('test-key', 'qwen/test-model');
    $result = $service->chat([
        ['role' => 'user', 'content' => 'Hello'],
    ]);

    expect($result)
        ->toBeInstanceOf(ChatResult::class)
        ->and($result->content)->toBe('This is the answer.')
        ->and($result->model)->toBe('qwen/test-model')
        ->and($result->promptTokens)->toBe(10)
        ->and($result->completionTokens)->toBe(20)
        ->and($result->costUsd)->toBe(0.0003)
        ->and($result->durationMs)->toBeGreaterThanOrEqual(0);

    Http::assertSent(function ($request) {
        return $request['usage'] === ['include' => true];
    });
});

test('throws custom exception on failed response', function () {
    Http::fake([
        'openrouter.ai/api/v1/chat/completions' => Http::response('Unauthorized', 401),
    ]);

    $service = new OpenRouterService('test-key', 'qwen/test-model');
    $service->chat([
        ['role' => 'user', 'content' => 'Hello'],
    ]);
})->throws(OpenRouterException::class);

test('throws timeout exception on connection exception', function () {
    Http::fake(function () {
        throw new ConnectionException('cURL error 28');
    });

    $service = new OpenRouterService('test-key', 'qwen/test-model');
    $service->chat([
        ['role' => 'user', 'content' => 'Hello'],
    ]);
})->throws(OpenRouterTimeoutException::class);

test('uses provided model override', function () {
    Http::fake([
        'openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'OK',
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new OpenRouterService('test-key', 'qwen/test-model');
    $result = $service->chat([
        ['role' => 'user', 'content' => 'Hello'],
    ], 'other-model');

    expect($result->content)->toBe('OK')
        ->and($result->model)->toBe('other-model');

    Http::assertSent(function ($request) {
        return $request['model'] === 'other-model';
    });
});

test('allows null usage fields', function () {
    Http::fake([
        'openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'Answer without usage.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new OpenRouterService('test-key', 'qwen/test-model');
    $result = $service->chat([
        ['role' => 'user', 'content' => 'Hello'],
    ]);

    expect($result->content)->toBe('Answer without usage.')
        ->and($result->promptTokens)->toBeNull()
        ->and($result->completionTokens)->toBeNull()
        ->and($result->costUsd)->toBeNull();
});

test('returns key usage data', function (): void {
    Http::fake([
        'openrouter.ai/api/v1/auth/key' => Http::response([
            'data' => [
                'limit' => 100.0,
                'usage' => 25.5,
                'limit_remaining' => 74.5,
            ],
        ], 200),
    ]);

    $service = new OpenRouterService('test-key', 'qwen/test-model');
    $result = $service->getKeyUsage();

    expect($result['limit'])->toEqual(100.0)
        ->and($result['usage'])->toEqual(25.5)
        ->and($result['remaining'])->toEqual(74.5);
});

test('returns nulls when key usage endpoint fails', function (): void {
    Http::fake([
        'openrouter.ai/api/v1/auth/key' => Http::response(['error' => 'forbidden'], 403),
    ]);

    $service = new OpenRouterService('test-key', 'qwen/test-model');
    $result = $service->getKeyUsage();

    expect($result)->toBe([
        'limit' => null,
        'usage' => null,
        'remaining' => null,
    ]);
});
