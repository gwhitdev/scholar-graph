<?php

use App\Models\Paper;
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
