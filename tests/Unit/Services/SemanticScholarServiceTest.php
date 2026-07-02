<?php

use App\Exceptions\SemanticScholarRateLimitException;
use App\Services\SemanticScholarService;
use Illuminate\Support\Facades\Http;

test('builds correct search url', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response(['data' => []], 200),
    ]);

    $service = new SemanticScholarService('https://api.semanticscholar.org');
    $service->search('transformer');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'paper/search')
            && str_contains($request->url(), 'query=transformer');
    });
});

test('maps api response to normalized shape', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'data' => [
                [
                    'paperId' => 'abc123',
                    'title' => 'Test Paper',
                    'abstract' => 'An abstract',
                    'year' => 2023,
                    'externalIds' => ['DOI' => '10.1234/test'],
                ],
            ],
        ], 200),
    ]);

    $service = new SemanticScholarService('https://api.semanticscholar.org');
    $results = $service->search('test');

    expect($results)->toHaveCount(1);
    expect($results[0]['semantic_scholar_id'])->toBe('abc123');
    expect($results[0]['title'])->toBe('Test Paper');
    expect($results[0]['abstract'])->toBe('An abstract');
    expect($results[0]['year'])->toBe(2023);
});

test('throws rate limit exception on 429', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([], 429),
    ]);

    $service = new SemanticScholarService('https://api.semanticscholar.org');
    $service->search('test');
})->throws(SemanticScholarRateLimitException::class);
