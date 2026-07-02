<?php

namespace App\Policies;

use App\Models\Collection;
use App\Models\User;

class CollectionPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Collection $collection): bool
    {
        return $user->id === $collection->project->user_id;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Collection $collection): bool
    {
        return $user->id === $collection->project->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Collection $collection): bool
    {
        return $user->id === $collection->project->user_id;
    }

    /**
     * Determine whether the user can add a paper to the collection.
     */
    public function addPaper(User $user, Collection $collection): bool
    {
        return $user->id === $collection->project->user_id;
    }

    /**
     * Determine whether the user can remove a paper from the collection.
     */
    public function removePaper(User $user, Collection $collection): bool
    {
        return $user->id === $collection->project->user_id;
    }
}
