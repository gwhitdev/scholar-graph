<?php

use App\Services\OpenAlexSearchService;
use Illuminate\Support\Facades\Http;

test('search normalizes OpenAlex response to expected shape', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response([
            'results' => [[
                'id' => 'https://openalex.org/W2741809807',
                'doi' => 'https://doi.org/10.1038/nature12373',
                'title' => 'Deep Learning',
                'publication_year' => 2015,
                'cited_by_count' => 45000,
                'abstract_inverted_index' => ['Deep' => [0], 'learning' => [1, 7], 'is' => [2]],
                'authorships' => [
                    ['author' => ['display_name' => 'Yann LeCun']],
                    ['author' => ['display_name' => 'Yoshua Bengio']],
                ],
                'primary_location' => ['source' => ['display_name' => 'Nature']],
                'referenced_works' => ['https://openalex.org/W123'],
            ]],
            'meta' => ['count' => 1],
        ], 200),
    ]);

    $service = new OpenAlexSearchService('https://api.openalex.org', 'test@example.com');
    $results = $service->search('deep learning');

    expect($results)->toHaveCount(1);
    expect($results[0]['openalex_id'])->toBe('W2741809807');
    expect($results[0]['doi'])->toBe('10.1038/nature12373');
    expect($results[0]['title'])->toBe('Deep Learning');
    expect($results[0]['year'])->toBe(2015);
    expect($results[0]['authors'])->toBe(['Yann LeCun', 'Yoshua Bengio']);
    expect($results[0]['venue'])->toBe('Nature');
    expect($results[0]['cited_by_count'])->toBe(45000);
    expect($results[0]['referenced_works'])->toBe(['W123']);
    expect($results[0]['abstract'])->toBeString();
});

test('reconstruct abstract from inverted index', function () {
    $service = new OpenAlexSearchService('https://api.openalex.org', 'test@example.com');

    $index = [
        'Hello' => [0],
        'world' => [1],
        'foo' => [3],
        'bar' => [2],
    ];

    $abstract = $service->reconstructAbstract($index);

    expect($abstract)->toBe('Hello world bar foo');
});

test('search caches results and does not repeat api calls', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response([
            'results' => [[
                'id' => 'https://openalex.org/W1',
                'title' => 'Cached Paper',
                'publication_year' => 2024,
                'authorships' => [],
                'primary_location' => null,
                'abstract_inverted_index' => null,
                'referenced_works' => [],
                'cited_by_count' => 0,
                'doi' => null,
            ]],
            'meta' => ['count' => 1],
        ], 200),
    ]);

    $service = new OpenAlexSearchService('https://api.openalex.org', 'test@example.com');
    $service->search('cacheable query');
    $service->search('cacheable query');

    Http::assertSentCount(1);
});

test('getWork caches results', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response([
            'id' => 'https://openalex.org/W1',
            'title' => 'Single Work',
            'publication_year' => 2023,
            'authorships' => [],
            'primary_location' => null,
            'abstract_inverted_index' => null,
            'referenced_works' => [],
            'cited_by_count' => 10,
            'doi' => null,
        ], 200),
    ]);

    $service = new OpenAlexSearchService('https://api.openalex.org', 'test@example.com');
    $service->getWork('W1');
    $service->getWork('W1');

    Http::assertSentCount(1);
});

test('handles missing abstract gracefully', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response([
            'results' => [[
                'id' => 'https://openalex.org/W1',
                'title' => 'No Abstract Paper',
                'publication_year' => 2024,
                'authorships' => [],
                'primary_location' => null,
                'abstract_inverted_index' => null,
                'referenced_works' => [],
                'cited_by_count' => 0,
                'doi' => null,
            ]],
            'meta' => ['count' => 1],
        ], 200),
    ]);

    $service = new OpenAlexSearchService('https://api.openalex.org', 'test@example.com');
    $results = $service->search('test');

    expect($results[0]['abstract'])->toBeNull();
});

test('search sends mailto param for polite pool', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response(['results' => [], 'meta' => ['count' => 0]], 200),
    ]);

    $service = new OpenAlexSearchService('https://api.openalex.org', 'test@example.com');
    $service->search('test');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'mailto=test%40example.com')
            || str_contains($request->url(), 'mailto=test@example.com');
    });
});

test('search sends mailto in user agent header for polite pool', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response(['results' => [], 'meta' => ['count' => 0]], 200),
    ]);

    $service = new OpenAlexSearchService('https://api.openalex.org', 'test@example.com');
    $service->search('test');

    Http::assertSent(function ($request) {
        return str_contains($request->header('User-Agent')[0] ?? '', 'mailto:test@example.com');
    });
});

test('search omits mailto param when not configured', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response(['results' => [], 'meta' => ['count' => 0]], 200),
    ]);

    $service = new OpenAlexSearchService('https://api.openalex.org', '');
    $service->search('test');

    Http::assertSent(function ($request) {
        return ! str_contains($request->url(), 'mailto');
    });
});
