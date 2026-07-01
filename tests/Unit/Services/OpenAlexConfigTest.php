<?php

use App\Services\OpenAlexSearchService;

test('openalex config resolves with defaults', function () {
    expect(config('services.openalex.base_url'))->toBe('https://api.openalex.org');
});

test('openalex search service can be resolved from container', function () {
    $service = app(OpenAlexSearchService::class);

    expect($service)->toBeInstanceOf(OpenAlexSearchService::class);
});
