<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('scopes projects to the owner', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Project::factory()->for($userA)->create();
    Project::factory()->for($userB)->create();

    $projects = Project::ownedBy($userA)->get();

    expect($projects)->toHaveCount(1)
        ->and($projects->first()->user_id)->toBe($userA->id);
});
