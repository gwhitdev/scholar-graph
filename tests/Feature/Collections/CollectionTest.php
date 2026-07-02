<?php

use App\Models\Collection;
use App\Models\Paper;
use App\Models\Project;
use App\Models\User;

test('lets a user create a collection in their project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('collections.store', $project), [
            'name' => 'Methods papers',
            'color' => 'teal',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('collections', [
        'project_id' => $project->id,
        'user_id' => $user->id,
        'name' => 'Methods papers',
        'color' => 'teal',
        'position' => 0,
    ]);
});

test('lists a projects collections ordered by position', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $second = Collection::factory()->for($project)->create(['position' => 2, 'name' => 'Second']);
    $first = Collection::factory()->for($project)->create(['position' => 1, 'name' => 'First']);
    $third = Collection::factory()->for($project)->create(['position' => 3, 'name' => 'Third']);

    $response = $this->actingAs($user)
        ->get(route('collections.index', $project))
        ->assertOk();

    $ids = collect($response->json('collections'))->pluck('id')->all();

    expect($ids)->toBe([$first->id, $second->id, $third->id]);
});

test('forbids creating a collection in another users project', function () {
    $user = User::factory()->create();
    $otherProject = Project::factory()->create();

    $this->actingAs($user)
        ->post(route('collections.store', $otherProject), [
            'name' => 'Methods papers',
            'color' => 'teal',
        ])
        ->assertForbidden();
});

test('lets a user rename and recolour a collection', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $collection = Collection::factory()->for($project)->create(['name' => 'Old name', 'color' => 'sage']);

    $this->actingAs($user)
        ->patch(route('collections.update', [$project, $collection]), [
            'name' => 'New name',
            'color' => 'plum',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('collections', [
        'id' => $collection->id,
        'name' => 'New name',
        'color' => 'plum',
    ]);
});

test('rejects an invalid colour', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('collections.store', $project), [
            'name' => 'Methods papers',
            'color' => 'hotpink',
        ])
        ->assertSessionHasErrors('color');
});

test('lets a user add a project paper to a collection', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $paper = Paper::factory()->forProject($project, $user)->create();
    $collection = Collection::factory()->for($project)->create();

    $this->actingAs($user)
        ->post(route('collections.papers.add', [$project, $collection]), [
            'paper_id' => $paper->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('collection_paper', [
        'collection_id' => $collection->id,
        'paper_id' => $paper->id,
    ]);
});

test('rejects adding a paper that is not attached to the project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $otherProject = Project::factory()->for($user)->create();
    $paper = Paper::factory()->forProject($otherProject, $user)->create();
    $collection = Collection::factory()->for($project)->create();

    $this->actingAs($user)
        ->post(route('collections.papers.add', [$project, $collection]), [
            'paper_id' => $paper->id,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('collection_paper', [
        'collection_id' => $collection->id,
        'paper_id' => $paper->id,
    ]);
});

test('lets a user remove a paper from a collection', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $paper = Paper::factory()->forProject($project, $user)->create();
    $collection = Collection::factory()->for($project)->create();
    $collection->papers()->attach($paper);

    $this->actingAs($user)
        ->delete(route('collections.papers.remove', [$project, $collection, $paper]))
        ->assertRedirect();

    $this->assertDatabaseMissing('collection_paper', [
        'collection_id' => $collection->id,
        'paper_id' => $paper->id,
    ]);

    $this->assertDatabaseHas('project_papers', [
        'project_id' => $project->id,
        'paper_id' => $paper->id,
    ]);
});

test('deletes a collection without deleting its papers', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $paper = Paper::factory()->forProject($project, $user)->create();
    $collection = Collection::factory()->for($project)->create();
    $collection->papers()->attach($paper);

    $this->actingAs($user)
        ->delete(route('collections.destroy', [$project, $collection]))
        ->assertRedirect();

    $this->assertDatabaseMissing('collections', ['id' => $collection->id]);
    $this->assertDatabaseMissing('collection_paper', ['collection_id' => $collection->id]);
    $this->assertDatabaseHas('papers', ['id' => $paper->id]);
    $this->assertDatabaseHas('project_papers', [
        'project_id' => $project->id,
        'paper_id' => $paper->id,
    ]);
});

test('forbids adding a paper to another users collection', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherProject = Project::factory()->for($otherUser)->create();
    $paper = Paper::factory()->forProject($otherProject, $otherUser)->create();
    $collection = Collection::factory()->for($otherProject)->create();

    $this->actingAs($user)
        ->post(route('collections.papers.add', [$otherProject, $collection]), [
            'paper_id' => $paper->id,
        ])
        ->assertForbidden();
});

test('lets a user reorder collections', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $first = Collection::factory()->for($project)->create(['position' => 0, 'name' => 'First']);
    $second = Collection::factory()->for($project)->create(['position' => 1, 'name' => 'Second']);

    $this->actingAs($user)
        ->patch(route('collections.reorder', $project), [
            'collection_ids' => [$second->id, $first->id],
        ])
        ->assertRedirect();

    expect(Collection::find($second->id)->position)->toBe(0);
    expect(Collection::find($first->id)->position)->toBe(1);
});

test('forbids reordering collections in another users project', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherProject = Project::factory()->for($otherUser)->create();
    $collection = Collection::factory()->for($otherProject)->create();

    $this->actingAs($user)
        ->patch(route('collections.reorder', $otherProject), [
            'collection_ids' => [$collection->id],
        ])
        ->assertForbidden();
});
