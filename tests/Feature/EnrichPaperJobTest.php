<?php

use App\Actions\Papers\GeneratePaperSummaryAction;
use App\Jobs\EnrichPaperJob;
use App\Models\Paper;
use App\Models\PaperEnrichment;
use App\Services\SemanticScholarService;
use Illuminate\Support\Facades\Http;

function runEnrichJob(Paper $paper): void
{
    $job = new EnrichPaperJob($paper);
    $job->handle(
        app(SemanticScholarService::class),
        app(GeneratePaperSummaryAction::class),
    );
}

test('job stores s2 tldr when available', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'tldr' => ['text' => 'AI-generated summary.'],
            'influentialCitationCount' => 150,
        ], 200),
    ]);

    $paper = Paper::factory()->create(['doi' => '10.1038/test']);

    runEnrichJob($paper);

    $enrichment = PaperEnrichment::where('paper_id', $paper->id)->first();

    expect($enrichment)->not->toBeNull()
        ->and($enrichment->tldr)->toBe('AI-generated summary.')
        ->and($enrichment->tldr_source)->toBe('semantic_scholar')
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

    runEnrichJob($paper);

    Http::assertNothingSent();
});

test('job generates summary when s2 has no tldr', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'tldr' => null,
            'influentialCitationCount' => 26,
        ], 200),
        'dashscope-intl.aliyuncs.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Generated fallback summary.']],
            ],
        ], 200),
    ]);

    $paper = Paper::factory()->create([
        'doi' => '10.1038/test',
        'abstract' => 'An abstract to summarize.',
    ]);

    runEnrichJob($paper);

    $enrichment = PaperEnrichment::where('paper_id', $paper->id)->first();

    expect($enrichment)->not->toBeNull()
        ->and($enrichment->tldr)->toBe('Generated fallback summary.')
        ->and($enrichment->tldr_source)->toBe('generated')
        ->and($enrichment->influential_citation_count)->toBe(26);
});

test('job generates summary when s2 is rate limited', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([], 429),
        'dashscope-intl.aliyuncs.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Generated despite rate limit.']],
            ],
        ], 200),
    ]);

    $paper = Paper::factory()->create([
        'doi' => '10.1038/test',
        'abstract' => 'An abstract to summarize.',
    ]);

    runEnrichJob($paper);

    $enrichment = PaperEnrichment::where('paper_id', $paper->id)->first();

    expect($enrichment)->not->toBeNull()
        ->and($enrichment->tldr)->toBe('Generated despite rate limit.')
        ->and($enrichment->tldr_source)->toBe('generated')
        ->and($enrichment->influential_citation_count)->toBeNull();
});

test('job generates summary when paper has no doi', function () {
    Http::fake([
        'dashscope-intl.aliyuncs.com/*' => Http::response([
            'choices' => [
                ['message' => ['content' => 'Summary without S2.']],
            ],
        ], 200),
    ]);

    $paper = Paper::factory()->create([
        'doi' => null,
        'abstract' => 'An abstract to summarize.',
    ]);

    runEnrichJob($paper);

    $enrichment = PaperEnrichment::where('paper_id', $paper->id)->first();

    expect($enrichment)->not->toBeNull()
        ->and($enrichment->tldr)->toBe('Summary without S2.')
        ->and($enrichment->tldr_source)->toBe('generated');

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'semanticscholar'));
});

test('job creates no enrichment when s2 fails and paper has no abstract', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([], 429),
    ]);

    $paper = Paper::factory()->create([
        'doi' => '10.1038/test',
        'abstract' => null,
    ]);

    runEnrichJob($paper);

    expect(PaperEnrichment::where('paper_id', $paper->id)->exists())->toBeFalse();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'dashscope'));
});
