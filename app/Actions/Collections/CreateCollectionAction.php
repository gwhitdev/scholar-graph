<?php

namespace App\Actions\Collections;

use App\Models\Collection;
use App\Models\Project;
use App\Models\User;

class CreateCollectionAction
{
    /**
     * Create a new collection within the given project.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Project $project, User $user, array $data): Collection
    {
        return Collection::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'name' => $data['name'],
            'color' => $data['color'],
            'position' => $project->collections()->count(),
        ]);
    }
}
