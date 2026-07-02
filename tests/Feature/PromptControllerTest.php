<?php

use App\Models\Project;
use App\Models\User;

test('guest cannot update project prompt', function () {
    $project = Project::factory()->create();

    $this->put(route('projects.prompt.update', $project), [
        'system_prompt' => 'Test prompt',
    ])->assertRedirect(route('login'));
});

test('user can update project prompt', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->put(route('projects.prompt.update', $project), [
            'system_prompt' => 'You are a specialized assistant.',
            'use_global_prompt' => false,
            'negative_prompt' => 'Do not use bullet points.',
        ])
        ->assertRedirect();

    $project->refresh();
    expect($project->system_prompt)->toBe('You are a specialized assistant.');
    expect($project->use_global_prompt)->toBeFalse();
    expect($project->negative_prompt)->toBe('Do not use bullet points.');
});

test('user cannot update another users project prompt', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->put(route('projects.prompt.update', $project), [
            'system_prompt' => 'Hacked prompt',
        ])
        ->assertForbidden();
});

test('user can toggle use global prompt', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create([
        'use_global_prompt' => true,
    ]);

    $this->actingAs($user)
        ->put(route('projects.prompt.update', $project), [
            'use_global_prompt' => false,
        ])
        ->assertRedirect();

    $project->refresh();
    expect($project->use_global_prompt)->toBeFalse();
});
