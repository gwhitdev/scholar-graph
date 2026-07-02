<?php

use App\Models\Paper;
use App\Models\PaperEnrichment;
use App\Models\Project;

test('paper belongs to a project', function () {
    $paper = Paper::factory()->create();

    expect($paper->project)->toBeInstanceOf(Project::class);
});

test('deleting a project cascades to its papers', function () {
    $project = Project::factory()->has(Paper::factory()->count(3))->create();

    $project->delete();

    expect(Paper::count())->toBe(0);
});

test('paper has openalex fields in fillable', function () {
    $paper = new Paper;

    expect($paper->getFillable())->toContain('openalex_id', 'cited_by_count', 'referenced_works');
});

test('paper does not have removed fields in fillable', function () {
    $paper = new Paper;

    expect($paper->getFillable())->not->toContain('raw_metadata', 'semantic_scholar_id');
});

test('referenced_works casts to array', function () {
    $paper = Paper::factory()->create([
        'referenced_works' => ['W123', 'W456'],
    ]);

    expect($paper->fresh()->referenced_works)->toBeArray()
        ->and($paper->fresh()->referenced_works)->toBe(['W123', 'W456']);
});

test('paper has enrichment hasone relationship', function () {
    $paper = Paper::factory()->create();
    $enrichment = PaperEnrichment::factory()->create(['paper_id' => $paper->id]);

    expect($paper->enrichment)->toBeInstanceOf(PaperEnrichment::class)
        ->and($paper->enrichment->id)->toBe($enrichment->id);
});
