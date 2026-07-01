<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;

class CreateProjectAction
{
    public function handle(User $user, string $name): Project
    {
        return $user->projects()->create([
            'name' => $name,
        ]);
    }
}
