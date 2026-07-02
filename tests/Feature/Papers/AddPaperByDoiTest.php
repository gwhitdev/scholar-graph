<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('a user can add a paper by DOI to their project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Http::fake([
        'api.openalex.org/works*' => Http::response([
            'results' => [
                [
                    'id' => 'https://openalex.org/W1234567890',
                    'doi' => 'https://doi.org/10.1234/test-paper',
                    'title' => 'Test Paper Title',
                    'publication_year' => 2023,
                    'authorships' => [
                        ['author' => ['display_name' => 'Jane Doe']],
                    ],
                    'primary_location' => [
                        'source' => ['display_name' => 'Test Journal'],
                    ],
                    'cited_by_count' => 42,
                    'abstract_inverted_index' => null,
                    'referenced_works' => [],
                ],
            ],
        ], 200),
    ]);

    $this->actingAs($user)
        ->post(route('papers.doi.store', $project), [
            'doi' => '10.1234/test-paper',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('papers', [
        'doi' => '10.1234/test-paper',
        'title' => 'Test Paper Title',
    ]);

    $this->assertDatabaseHas('project_papers', [
        'project_id' => $project->id,
    ]);
});

test('an unknown DOI returns a validation error', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Http::fake([
        'api.openalex.org/works*' => Http::response([
            'results' => [],
        ], 200),
    ]);

    $this->actingAs($user)
        ->post(route('papers.doi.store', $project), [
            'doi' => '10.9999/nonexistent',
        ])
        ->assertSessionHasErrors('doi');
});

test('a user cannot add a paper by DOI to another users project', function () {
    $user = User::factory()->create();
    $otherProject = Project::factory()->create();

    $this->actingAs($user)
        ->post(route('papers.doi.store', $otherProject), [
            'doi' => '10.1234/test-paper',
        ])
        ->assertForbidden();
});

test('DOI field is required', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('papers.doi.store', $project), [])
        ->assertSessionHasErrors('doi');
});
