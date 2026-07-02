<?php

use App\Exceptions\OpenRouterException;
use App\Exceptions\OpenRouterTimeoutException;
use App\Services\OpenRouterService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

test('returns assistant content on successful response', function () {
    Http::fake([
        'openrouter.ai/api/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'This is the answer.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new OpenRouterService('test-key', 'qwen/test-model');
    $answer = $service->chat([
        ['role' => 'user', 'content' => 'Hello'],
    ]);

    expect($answer)->toBe('This is the answer.');
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
    $service->chat([
        ['role' => 'user', 'content' => 'Hello'],
    ], 'other-model');

    Http::assertSent(function ($request) {
        return $request['model'] === 'other-model';
    });
});
