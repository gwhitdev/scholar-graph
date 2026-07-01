<?php

use App\Exceptions\SemanticScholarRateLimitException;
use App\Services\SemanticScholarService;
use Illuminate\Support\Facades\Cache;
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
                    'authors' => [
                        ['authorId' => 'a1', 'name' => 'Alice Smith'],
                        ['authorId' => 'a2', 'name' => 'Bob Jones'],
                    ],
                    'externalIds' => ['DOI' => '10.1234/test'],
                    'venue' => 'Nature',
                    'journal' => ['name' => 'Nature', 'pages' => '10-20', 'volume' => '42'],
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
    expect($results[0]['raw_metadata']['authors'])->toHaveCount(2);
    expect($results[0]['raw_metadata']['authors'][0]['name'])->toBe('Alice Smith');
    expect($results[0]['raw_metadata']['externalIds']['DOI'])->toBe('10.1234/test');
    expect($results[0]['raw_metadata']['venue'])->toBe('Nature');
    expect($results[0]['raw_metadata']['journal']['pages'])->toBe('10-20');
});

test('search requests enriched fields from api', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response(['data' => []], 200),
    ]);

    $service = new SemanticScholarService('https://api.semanticscholar.org');
    $service->search('transformer');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'fields=')
            && str_contains($request->url(), 'authors')
            && str_contains($request->url(), 'externalIds')
            && str_contains($request->url(), 'title')
            && str_contains($request->url(), 'abstract');
    });
});

test('handles missing authors and external ids gracefully', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'data' => [
                [
                    'paperId' => 'xyz789',
                    'title' => 'Sparse Paper',
                    'year' => 2021,
                ],
            ],
        ], 200),
    ]);

    $service = new SemanticScholarService('https://api.semanticscholar.org');
    $results = $service->search('sparse');

    expect($results[0]['raw_metadata']['authors'] ?? [])->toBe([]);
    expect($results[0]['raw_metadata']['externalIds']['DOI'] ?? null)->toBeNull();
    expect($results[0]['raw_metadata']['venue'] ?? null)->toBeNull();
    expect($results[0]['raw_metadata']['journal']['pages'] ?? null)->toBeNull();
});

test('throws rate limit exception on 429', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([], 429),
    ]);

    $service = new SemanticScholarService('https://api.semanticscholar.org');
    $service->search('test');
})->throws(SemanticScholarRateLimitException::class);

test('search caches results and does not repeat api calls', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'data' => [
                ['paperId' => 'abc', 'title' => 'Cached Paper', 'year' => 2024],
            ],
        ], 200),
    ]);

    $service = new SemanticScholarService('https://api.semanticscholar.org');

    $results1 = $service->search('cacheable query');
    $results2 = $service->search('cacheable query');

    expect($results1)->toHaveCount(1);
    expect($results2)->toHaveCount(1);
    expect($results1[0]['title'])->toBe('Cached Paper');

    Http::assertSentCount(1);
});

test('search does not cache rate limit errors', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([], 429),
    ]);

    $service = new SemanticScholarService('https://api.semanticscholar.org');

    try {
        $service->search('fail query');
    } catch (SemanticScholarRateLimitException) {
        // expected
    }

    expect(Cache::get('semantic_scholar:search:fail query:10'))->toBeNull();
});

test('fetch paper caches results', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'paperId' => 'paper1',
            'title' => 'Fetched Paper',
            'year' => 2023,
        ], 200),
    ]);

    $service = new SemanticScholarService('https://api.semanticscholar.org');

    $paper1 = $service->fetchPaper('paper1');
    $paper2 = $service->fetchPaper('paper1');

    expect($paper1['title'])->toBe('Fetched Paper');
    expect($paper2['title'])->toBe('Fetched Paper');

    Http::assertSentCount(1);
});

test('different queries hit the api separately', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response(['data' => []], 200),
    ]);

    $service = new SemanticScholarService('https://api.semanticscholar.org');

    $service->search('query one');
    $service->search('query two');

    Http::assertSentCount(2);
});
