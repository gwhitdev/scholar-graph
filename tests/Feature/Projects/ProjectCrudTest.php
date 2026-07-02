<?php

use App\Models\Project;
use App\Models\User;

test('guests are redirected to login for project routes', function () {
    $this->get(route('projects.index'))->assertRedirect(route('login'));
    $this->post(route('projects.store'))->assertRedirect(route('login'));
    $this->get(route('projects.show', 1))->assertRedirect(route('login'));
    $this->delete(route('projects.destroy', 1))->assertRedirect(route('login'));
});

test('authenticated user can list their projects', function () {
    $user = User::factory()->has(Project::factory()->count(2))->create();

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('projects/index')
            ->has('projects', 2)
        );
});

test('authenticated user can create a project', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('projects.store'), ['name' => 'Transformer Review'])
        ->assertRedirect();

    $this->assertDatabaseHas('projects', [
        'user_id' => $user->id,
        'name' => 'Transformer Review',
    ]);
});

test('user cannot see another users project', function () {
    $user = User::factory()->create();
    $otherProject = Project::factory()->create();

    $this->actingAs($user)
        ->get(route('projects.show', $otherProject))
        ->assertForbidden();
});

test('user can delete their own project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('projects.destroy', $project))
        ->assertRedirect();

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});

test('project name is required and has a max length', function (array $data, string $errorKey) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('projects.store'), $data)
        ->assertSessionHasErrors($errorKey);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'long name' => [['name' => str_repeat('a', 256)], 'name'],
]);
