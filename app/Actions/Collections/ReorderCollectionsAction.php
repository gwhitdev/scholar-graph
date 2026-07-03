<?php

namespace App\Actions\Collections;

use App\Models\Project;

class ReorderCollectionsAction
{
    /**
     * Reorder the project's collections to match the given ID order.
     *
     * @param  list<int>  $orderedIds
     */
    public function handle(Project $project, array $orderedIds): void
    {
        $project->collections()
            ->whereKey($orderedIds)
            ->get()
            ->each(function ($collection) use ($orderedIds) {
                $position = array_search($collection->id, $orderedIds, true);

                if ($position !== false) {
                    $collection->update(['position' => $position]);
                }
            });
    }
}
