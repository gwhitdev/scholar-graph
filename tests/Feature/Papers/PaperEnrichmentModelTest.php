<?php

use App\Models\Paper;
use App\Models\PaperEnrichment;

test('paper enrichment belongs to paper', function () {
    $paper = Paper::factory()->create();
    $enrichment = PaperEnrichment::factory()->create(['paper_id' => $paper->id]);

    expect($enrichment->paper->id)->toBe($paper->id);
});

test('deleting paper cascades enrichment', function () {
    $paper = Paper::factory()->create();
    PaperEnrichment::factory()->create(['paper_id' => $paper->id]);

    $paper->delete();

    expect(PaperEnrichment::count())->toBe(0);
});
