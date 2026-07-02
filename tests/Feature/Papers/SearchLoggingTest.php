<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('records a search_queries row per search', function () {
    Http::fake([
        'api.openalex.org/*' => Http::response([
            'results' => [
                [
                    'id' => 'https://openalex.org/W123',
                    'title' => 'A Paper About Neural Networks',
                    'authorships' => [],
                    'publication_year' => 2024,
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('papers.search', ['project' => $project, 'query' => 'neural networks', 'limit' => 5]))
        ->assertOk();

    $this->assertDatabaseHas('search_queries', [
        'user_id' => $user->id,
        'project_id' => $project->id,
        'query' => 'neural networks',
        'source' => 'openalex',
        'result_count' => 1,
    ]);
});
