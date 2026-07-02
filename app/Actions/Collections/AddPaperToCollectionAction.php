<?php

namespace App\Actions\Collections;

use App\Models\Collection;
use App\Models\Paper;
use Illuminate\Auth\Access\AuthorizationException;

class AddPaperToCollectionAction
{
    /**
     * Attach the paper to the collection, guarding that the paper belongs to the project.
     *
     * @throws AuthorizationException
     */
    public function handle(Collection $collection, Paper $paper): void
    {
        if ($collection->project->papers()->whereKey($paper->id)->doesntExist()) {
            throw new AuthorizationException('The paper is not attached to this project.');
        }

        $collection->papers()->syncWithoutDetaching($paper);
    }
}
