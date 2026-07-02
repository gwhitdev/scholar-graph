<?php

use App\Jobs\EnrichPaperJob;
use App\Models\Paper;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('guest cannot search papers', function () {
    $project = Project::factory()->create();

    $this->getJson(route('papers.search', $project))
        ->assertUnauthorized();
});

test('user can search papers', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response([
            'results' => [
                [
                    'id' => 'https://openalex.org/W2741809807',
                    'doi' => 'https://doi.org/10.1038/nature12373',
                    'title' => 'Deep Learning',
                    'publication_year' => 2015,
                    'cited_by_count' => 45000,
                    'abstract_inverted_index' => ['Deep' => [0], 'learning' => [1]],
                    'authorships' => [
                        ['author' => ['display_name' => 'Yann LeCun']],
                        ['author' => ['display_name' => 'Yoshua Bengio']],
                    ],
                    'primary_location' => ['source' => ['display_name' => 'Nature']],
                    'referenced_works' => [],
                ],
            ],
            'meta' => ['count' => 1],
        ], 200),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->getJson(route('papers.search', ['project' => $project, 'query' => 'deep learning']));

    $response->assertOk()
        ->assertJsonStructure([
            ['openalex_id', 'title', 'abstract', 'year', 'authors', 'doi', 'venue', 'cited_by_count'],
        ])
        ->assertJsonFragment([
            'openalex_id' => 'W2741809807',
            'title' => 'Deep Learning',
            'year' => 2015,
        ]);
});

test('search returns friendly error when openalex is unavailable', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response([
            'error' => 'Search temporarily unavailable',
            'message' => 'Anonymous search is temporarily unavailable.',
        ], 503),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson(route('papers.search', ['project' => $project, 'query' => 'test']))
        ->assertStatus(503)
        ->assertJson(['error' => 'Paper search is temporarily unavailable. Please try again shortly.']);
});

test('user can add a paper to own project', function () {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('papers.store', $project), [
            'title' => 'Test Paper',
            'abstract' => 'An abstract',
            'year' => 2023,
            'openalex_id' => 'W2741809807',
            'authors' => ['Alice Smith', 'Bob Jones'],
            'doi' => '10.1038/test',
            'venue' => 'Nature',
            'cited_by_count' => 100,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('papers', [
        'project_id' => $project->id,
        'title' => 'Test Paper',
        'openalex_id' => 'W2741809807',
        'doi' => '10.1038/test',
        'venue' => 'Nature',
        'cited_by_count' => 100,
    ]);

    $paper = Paper::where('openalex_id', 'W2741809807')->first();
    expect($paper->authors)->toBe(['Alice Smith', 'Bob Jones']);

    Queue::assertPushed(EnrichPaperJob::class);
});

test('adding same paper twice does not create duplicate', function () {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $data = [
        'title' => 'Test Paper',
        'abstract' => 'An abstract',
        'year' => 2023,
        'openalex_id' => 'W2741809807',
    ];

    $this->actingAs($user)
        ->post(route('papers.store', $project), $data);

    $this->actingAs($user)
        ->post(route('papers.store', $project), $data);

    $this->assertDatabaseCount('papers', 1);
});

test('user cannot add paper to another users project', function () {
    $user = User::factory()->create();
    $otherProject = Project::factory()->create();

    $this->actingAs($user)
        ->post(route('papers.store', $otherProject), [
            'title' => 'Test Paper',
            'openalex_id' => 'W2741809807',
        ])
        ->assertForbidden();
});

test('user can delete own project paper', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $paper = Paper::factory()->for($project)->create();

    $this->actingAs($user)
        ->delete(route('papers.destroy', [$project, $paper]))
        ->assertRedirect();

    $this->assertDatabaseMissing('papers', ['id' => $paper->id]);
});

test('user can trigger enrichment on a paper', function () {
    Queue::fake();

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $paper = Paper::factory()->for($project)->create(['doi' => '10.1038/test']);

    $this->actingAs($user)
        ->postJson(route('papers.enrich', [$project, $paper]))
        ->assertStatus(202);

    Queue::assertPushed(EnrichPaperJob::class);
});

test('user cannot trigger enrichment on another users paper', function () {
    $user = User::factory()->create();
    $otherProject = Project::factory()->create();
    $paper = Paper::factory()->for($otherProject)->create(['doi' => '10.1038/test']);

    $this->actingAs($user)
        ->postJson(route('papers.enrich', [$otherProject, $paper]))
        ->assertForbidden();
});

test('user cannot enrich paper from different project via own project', function () {
    $user = User::factory()->create();
    $ownProject = Project::factory()->for($user)->create();
    $otherProject = Project::factory()->create();
    $otherPaper = Paper::factory()->for($otherProject)->create(['doi' => '10.1038/test']);

    $this->actingAs($user)
        ->postJson(route('papers.enrich', [$ownProject, $otherPaper]))
        ->assertNotFound();
});
