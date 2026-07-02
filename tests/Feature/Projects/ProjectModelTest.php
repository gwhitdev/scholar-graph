<?php

use App\Models\Paper;
use App\Models\Project;
use App\Models\User;

test('project belongs to a user', function () {
    $project = Project::factory()->create();

    expect($project->user)->toBeInstanceOf(User::class);
});

test('project has many papers', function () {
    $project = Project::factory()->create();

    Paper::factory()->count(3)->forProject($project)->create();

    expect($project->papers)->toHaveCount(3);
});

test('user only sees their own projects', function () {
    $user = User::factory()->has(Project::factory()->count(2))->create();
    User::factory()->has(Project::factory()->count(3))->create();

    $projects = Project::where('user_id', $user->id)->get();

    expect($projects)->toHaveCount(2);
});
