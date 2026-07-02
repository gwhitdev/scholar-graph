<?php

use App\Models\ChatMessage;
use App\Models\Project;
use App\Models\Synthesis;

test('paper ids are cast to an array', function () {
    $synthesis = Synthesis::factory()->create([
        'paper_ids' => [1, 2, 3],
    ]);

    expect($synthesis->paper_ids)->toBeArray()->toBe([1, 2, 3]);
});

test('deleting a synthesis sets chat message synthesis id to null', function () {
    $project = Project::factory()->create();
    $synthesis = Synthesis::factory()->for($project)->create();
    $message = ChatMessage::factory()->for($project)->create([
        'synthesis_id' => $synthesis->id,
    ]);

    $synthesis->delete();

    expect($message->fresh()->synthesis_id)->toBeNull();
});
