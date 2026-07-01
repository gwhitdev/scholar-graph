<?php

use App\Jobs\EnrichPaperJob;
use App\Models\Paper;
use App\Models\PaperEnrichment;
use App\Services\SemanticScholarService;
use Illuminate\Support\Facades\Http;

test('job creates enrichment record on success', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'tldr' => ['text' => 'AI-generated summary.'],
            'influentialCitationCount' => 150,
        ], 200),
    ]);

    $paper = Paper::factory()->create(['doi' => '10.1038/test']);

    $job = new EnrichPaperJob($paper);
    $job->handle(app(SemanticScholarService::class));

    $enrichment = PaperEnrichment::where('paper_id', $paper->id)->first();

    expect($enrichment)->not->toBeNull()
        ->and($enrichment->tldr)->toBe('AI-generated summary.')
        ->and($enrichment->influential_citation_count)->toBe(150)
        ->and($enrichment->enriched_at)->not->toBeNull();
});

test('job skips if already enriched', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'tldr' => ['text' => 'Already done.'],
            'influentialCitationCount' => 50,
        ], 200),
    ]);

    $paper = Paper::factory()->create(['doi' => '10.1038/test']);
    PaperEnrichment::factory()->create([
        'paper_id' => $paper->id,
        'enriched_at' => now(),
    ]);

    $job = new EnrichPaperJob($paper);
    $job->handle(app(SemanticScholarService::class));

    Http::assertNothingSent();
});

test('job does not create enrichment on rate limit', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([], 429),
    ]);

    $paper = Paper::factory()->create(['doi' => '10.1038/test']);

    $job = new EnrichPaperJob($paper);
    $job->handle(app(SemanticScholarService::class));

    expect(PaperEnrichment::where('paper_id', $paper->id)->exists())->toBeFalse();
});

test('job bails when paper has no doi', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'tldr' => ['text' => 'Should not reach.'],
            'influentialCitationCount' => 0,
        ], 200),
    ]);

    $paper = Paper::factory()->create(['doi' => null]);

    $job = new EnrichPaperJob($paper);
    $job->handle(app(SemanticScholarService::class));

    Http::assertNothingSent();
    expect(PaperEnrichment::where('paper_id', $paper->id)->exists())->toBeFalse();
});
