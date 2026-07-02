<?php

use App\Enums\PaperStatus;
use App\Models\Paper;
use App\Models\PaperEnrichment;
use App\Models\Project;
use App\Models\User;

test('paper belongs to many projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $paper = Paper::factory()->create();

    $project->papers()->attach($paper, [
        'user_id' => $user->id,
        'status' => PaperStatus::Unread->value,
        'added_at' => now(),
    ]);

    expect($paper->projects->first()->id)->toBe($project->id);
});

test('deleting a project detaches its papers but keeps canonical papers', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $papers = Paper::factory()->count(3)->create();

    foreach ($papers as $paper) {
        $project->papers()->attach($paper, [
            'user_id' => $user->id,
            'status' => PaperStatus::Unread->value,
            'added_at' => now(),
        ]);
    }

    $project->delete();

    expect(Paper::count())->toBe(3);
    $this->assertDatabaseCount('project_papers', 0);
});

test('paper has openalex fields in fillable', function () {
    $paper = new Paper;

    expect($paper->getFillable())->toContain('openalex_id', 'cited_by_count', 'referenced_works');
});

test('paper does not have removed fields in fillable', function () {
    $paper = new Paper;

    expect($paper->getFillable())->not->toContain('raw_metadata', 'semantic_scholar_id', 'project_id', 'added_at');
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
