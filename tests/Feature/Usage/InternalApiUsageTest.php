<?php

use App\Models\Project;
use App\Models\User;

test('logs an internal api usage row for an authenticated page view', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('projects.show', $project))
        ->assertOk();

    $this->assertDatabaseHas('api_usage_logs', [
        'user_id' => $user->id,
        'source' => 'internal',
        'service' => 'app',
        'method' => 'GET',
        'status_code' => 200,
    ]);
});

test('skips logging for guests', function () {
    $this->get(route('login'))->assertOk();

    $this->assertDatabaseCount('api_usage_logs', 0);
});

test('skips logging for the health check', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/up')
        ->assertOk();

    $this->assertDatabaseCount('api_usage_logs', 0);
});
