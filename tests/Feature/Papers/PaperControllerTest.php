<?php

use App\Models\Paper;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('guest cannot search papers', function () {
    $project = Project::factory()->create();

    $this->getJson(route('papers.search', $project))
        ->assertUnauthorized();
});

test('user can search papers', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([
            'data' => [
                [
                    'paperId' => 'abc123',
                    'title' => 'Test Paper',
                    'abstract' => 'An abstract',
                    'year' => 2023,
                    'authors' => [
                        ['authorId' => 'a1', 'name' => 'Alice Smith'],
                    ],
                    'externalIds' => ['DOI' => '10.1234/test'],
                    'venue' => 'Nature',
                    'journal' => ['name' => 'Nature', 'pages' => '10-20', 'volume' => '42'],
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->getJson(route('papers.search', ['project' => $project, 'query' => 'transformer']));

    $response->assertOk()
        ->assertJsonStructure([
            ['semantic_scholar_id', 'title', 'abstract', 'year', 'raw_metadata'],
        ])
        ->assertJsonFragment([
            'semantic_scholar_id' => 'abc123',
            'title' => 'Test Paper',
            'abstract' => 'An abstract',
            'year' => 2023,
        ]);
});

test('rate limit returns 429', function () {
    Http::fake([
        'api.semanticscholar.org/*' => Http::response([], 429),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson(route('papers.search', ['project' => $project, 'query' => 'test']))
        ->assertStatus(429)
        ->assertJson(['error' => 'Search limit reached. Please try again in a few minutes.']);
});

test('user can add a paper to own project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('papers.store', $project), [
            'title' => 'Test Paper',
            'abstract' => 'An abstract',
            'year' => 2023,
            'semantic_scholar_id' => 'abc123',
            'raw_metadata' => [
                'authors' => [['name' => 'Alice Smith'], ['name' => 'Bob Jones']],
                'externalIds' => ['DOI' => '10.1234/test'],
                'venue' => 'Nature',
                'journal' => ['pages' => '10-20'],
            ],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('papers', [
        'project_id' => $project->id,
        'title' => 'Test Paper',
        'semantic_scholar_id' => 'abc123',
    ]);

    $paper = Paper::where('semantic_scholar_id', 'abc123')->first();
    expect($paper->raw_metadata['authors'][0]['name'])->toBe('Alice Smith');
    expect($paper->raw_metadata['externalIds']['DOI'])->toBe('10.1234/test');
});

test('adding same paper twice does not create duplicate', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $data = [
        'title' => 'Test Paper',
        'abstract' => 'An abstract',
        'year' => 2023,
        'semantic_scholar_id' => 'abc123',
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
            'abstract' => 'An abstract',
            'year' => 2023,
            'semantic_scholar_id' => 'abc123',
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
