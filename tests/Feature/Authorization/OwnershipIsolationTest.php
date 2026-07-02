<?php

use App\Models\Paper;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('forbids viewing another users project', function () {
    $userA = User::factory()->create();
    $projectB = Project::factory()->create();

    $this->actingAs($userA)
        ->get(route('projects.show', $projectB))
        ->assertForbidden();
});

it('forbids deleting another users project', function () {
    $userA = User::factory()->create();
    $projectB = Project::factory()->create();

    $this->actingAs($userA)
        ->delete(route('projects.destroy', $projectB))
        ->assertForbidden();

    $this->assertDatabaseHas('projects', ['id' => $projectB->id]);
});

it('forbids searching papers in another users project', function () {
    $userA = User::factory()->create();
    $projectB = Project::factory()->create();

    $this->actingAs($userA)
        ->get(route('papers.search', $projectB))
        ->assertForbidden();
});

it('forbids adding a paper to another users project', function () {
    $userA = User::factory()->create();
    $projectB = Project::factory()->create();

    $this->actingAs($userA)
        ->post(route('papers.store', $projectB), [
            'title' => 'A paper title',
        ])
        ->assertForbidden();
});

it('forbids enriching a paper in another users project', function () {
    $userA = User::factory()->create();
    $projectB = Project::factory()->create();
    $paperB = Paper::factory()->for($projectB)->create();

    $this->actingAs($userA)
        ->post(route('papers.enrich', [$projectB, $paperB]))
        ->assertForbidden();
});

it('forbids deleting a paper from another users project', function () {
    $userA = User::factory()->create();
    $projectB = Project::factory()->create();
    $paperB = Paper::factory()->for($projectB)->create();

    $this->actingAs($userA)
        ->delete(route('papers.destroy', [$projectB, $paperB]))
        ->assertForbidden();
});

it('forbids posting chat to another users project', function () {
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'hi']]]])]);

    $userA = User::factory()->create();
    $projectB = Project::factory()->create();

    $this->actingAs($userA)
        ->post(route('chat.store', $projectB), [
            'question' => 'What is the meaning of life?',
        ])
        ->assertForbidden();
});

it('forbids updating the prompt of another users project', function () {
    $userA = User::factory()->create();
    $projectB = Project::factory()->create();

    $this->actingAs($userA)
        ->put(route('projects.prompt.update', $projectB), [
            'system_prompt' => 'New prompt',
            'use_global_prompt' => false,
        ])
        ->assertForbidden();
});

it('returns 404 when a paper does not belong to the project in the url', function () {
    $projectX = Project::factory()->create();
    $paperX = Paper::factory()->for($projectX)->create();
    $projectY = Project::factory()->create();

    $this->actingAs($projectY->user)
        ->delete(route('papers.destroy', [$projectY, $paperX]))
        ->assertNotFound();
});

it('lets the owner perform each action', function () {
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'hi']]]])]);

    $userB = User::factory()->create();
    $projectB = Project::factory()->for($userB)->create();
    $paperB = Paper::factory()->for($projectB)->create();

    $this->actingAs($userB)
        ->get(route('projects.show', $projectB))
        ->assertOk();

    $this->actingAs($userB)
        ->get(route('papers.search', $projectB))
        ->assertOk();

    $this->actingAs($userB)
        ->post(route('papers.store', $projectB), [
            'title' => 'Owner paper',
        ])
        ->assertRedirect();

    $this->actingAs($userB)
        ->post(route('papers.enrich', [$projectB, $paperB]))
        ->assertAccepted();

    $this->actingAs($userB)
        ->put(route('projects.prompt.update', $projectB), [
            'system_prompt' => 'Updated prompt',
            'use_global_prompt' => false,
        ])
        ->assertRedirect();

    $this->actingAs($userB)
        ->post(route('chat.store', $projectB), [
            'question' => 'Owner question',
        ])
        ->assertRedirect();

    $newPaper = Paper::factory()->for($projectB)->create();
    $this->actingAs($userB)
        ->delete(route('papers.destroy', [$projectB, $newPaper]))
        ->assertRedirect();

    $newProject = Project::factory()->for($userB)->create();
    $this->actingAs($userB)
        ->delete(route('projects.destroy', $newProject))
        ->assertRedirect();
});
