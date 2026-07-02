<?php

use App\Enums\PaperStatus;
use App\Models\Paper;
use App\Models\PaperEnrichment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('deduplicates the same openalex paper across two users', function () {
    Queue::fake();

    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $projectA = Project::factory()->for($userA)->create();
    $projectB = Project::factory()->for($userB)->create();

    $data = [
        'title' => 'Shared Paper',
        'abstract' => 'An abstract',
        'year' => 2023,
        'openalex_id' => 'W1234567890',
        'authors' => ['Alice Smith'],
        'doi' => '10.1234/shared',
        'venue' => 'Nature',
        'cited_by_count' => 10,
    ];

    $this->actingAs($userA)
        ->post(route('papers.store', $projectA), $data)
        ->assertRedirect();

    $this->actingAs($userB)
        ->post(route('papers.store', $projectB), $data)
        ->assertRedirect();

    expect(Paper::count())->toBe(1);
    $this->assertDatabaseCount('project_papers', 2);
    $this->assertDatabaseHas('project_papers', [
        'project_id' => $projectA->id,
        'paper_id' => Paper::first()->id,
        'user_id' => $userA->id,
        'status' => PaperStatus::Unread->value,
    ]);
    $this->assertDatabaseHas('project_papers', [
        'project_id' => $projectB->id,
        'paper_id' => Paper::first()->id,
        'user_id' => $userB->id,
        'status' => PaperStatus::Unread->value,
    ]);
});

test('shares enrichment across projects', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $projectA = Project::factory()->for($userA)->create();
    $projectB = Project::factory()->for($userB)->create();

    $paper = Paper::factory()->create([
        'openalex_id' => 'W1234567890',
        'title' => 'Shared Paper',
    ]);

    $projectA->papers()->attach($paper, [
        'user_id' => $userA->id,
        'status' => PaperStatus::Unread->value,
        'added_at' => now(),
    ]);

    $projectB->papers()->attach($paper, [
        'user_id' => $userB->id,
        'status' => PaperStatus::Unread->value,
        'added_at' => now(),
    ]);

    PaperEnrichment::factory()->create([
        'paper_id' => $paper->id,
        'tldr' => 'Shared TLDR',
    ]);

    $this->actingAs($userA)
        ->get(route('projects.show', $projectA))
        ->assertInertia(fn ($page) => $page
            ->component('projects/show')
            ->has('papers', 1)
            ->where('papers.0.id', $paper->id)
            ->has('papers.0.enrichment')
            ->where('papers.0.enrichment.tldr', 'Shared TLDR'));

    $this->actingAs($userB)
        ->get(route('projects.show', $projectB))
        ->assertInertia(fn ($page) => $page
            ->component('projects/show')
            ->has('papers', 1)
            ->where('papers.0.id', $paper->id)
            ->has('papers.0.enrichment')
            ->where('papers.0.enrichment.tldr', 'Shared TLDR'));
});

test('detaching a paper from a project does not delete the shared paper', function () {
    $user = User::factory()->create();
    $projectA = Project::factory()->for($user)->create();
    $projectB = Project::factory()->for($user)->create();

    $paper = Paper::factory()->create(['openalex_id' => 'W1234567890']);

    $projectA->papers()->attach($paper, [
        'user_id' => $user->id,
        'status' => PaperStatus::Unread->value,
        'added_at' => now(),
    ]);

    $projectB->papers()->attach($paper, [
        'user_id' => $user->id,
        'status' => PaperStatus::Unread->value,
        'added_at' => now(),
    ]);

    $this->actingAs($user)
        ->delete(route('papers.destroy', [$projectA, $paper]))
        ->assertRedirect();

    expect(Paper::count())->toBe(1);
    $this->assertDatabaseCount('project_papers', 1);
    $this->assertDatabaseHas('project_papers', [
        'project_id' => $projectB->id,
        'paper_id' => $paper->id,
    ]);
});

test('stores per-project status on the pivot', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $paper = Paper::factory()->create(['openalex_id' => 'W1234567890']);

    $project->papers()->attach($paper, [
        'user_id' => $user->id,
        'status' => PaperStatus::Unread->value,
        'added_at' => now(),
    ]);

    $this->actingAs($user)
        ->patch(route('papers.status', [$project, $paper]), [
            'status' => PaperStatus::Reading->value,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('project_papers', [
        'project_id' => $project->id,
        'paper_id' => $paper->id,
        'status' => PaperStatus::Reading->value,
    ]);
});
