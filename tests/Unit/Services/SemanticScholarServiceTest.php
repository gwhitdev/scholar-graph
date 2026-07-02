<?php

use App\Services\SemanticScholarService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new SemanticScholarService('https://api.semanticscholar.org');
});

test('enrich returns tldr and influential citation count', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'paperId' => 'abc123',
            'tldr' => ['text' => 'This paper introduces deep learning methods.'],
            'influentialCitationCount' => 342,
        ], 200),
    ]);

    $result = $this->service->enrich('10.1038/nature12373');

    expect($result)->toBeArray()
        ->and($result['tldr'])->toBe('This paper introduces deep learning methods.')
        ->and($result['influential_citation_count'])->toBe(342);
});

test('enrich returns null on 429', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([], 429),
    ]);

    $result = $this->service->enrich('10.1038/test');

    expect($result)->toBeNull();
});

test('enrich returns null on any http error', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response(['error' => 'server error'], 500),
    ]);

    $result = $this->service->enrich('10.1038/test');

    expect($result)->toBeNull();
});

test('enrich caches successful results', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'tldr' => ['text' => 'Cached summary.'],
            'influentialCitationCount' => 100,
        ], 200),
    ]);

    $this->service->enrich('10.1038/test');
    $this->service->enrich('10.1038/test');

    Http::assertSentCount(1);
});

test('enrich does not cache null results', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([], 429),
    ]);

    $this->service->enrich('10.1038/test');
    $this->service->enrich('10.1038/test');

    Http::assertSentCount(2);
});

test('getRelatedPapers returns normalized list', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'recommendedPapers' => [
                [
                    'paperId' => 'rec1',
                    'title' => 'Related Paper 1',
                    'year' => 2022,
                    'authors' => [['name' => 'Author A']],
                ],
                [
                    'paperId' => 'rec2',
                    'title' => 'Related Paper 2',
                    'year' => 2023,
                    'authors' => [['name' => 'Author B']],
                ],
            ],
        ], 200),
    ]);

    $results = $this->service->getRelatedPapers('abc123', 5);

    expect($results)->toHaveCount(2);
    expect($results[0]['semantic_scholar_id'])->toBe('rec1');
    expect($results[0]['title'])->toBe('Related Paper 1');
    expect($results[0]['authors'])->toBe(['Author A']);
});
