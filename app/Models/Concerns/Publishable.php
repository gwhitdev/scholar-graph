<?php

namespace App\Models\Concerns;

use App\Enums\PageStatus;

/**
 * Trait for models that support draft/published status.
 *
 * The model must have a `status` column cast to PageStatus.
 * Optionally supports a `published_at` datetime column.
 */
trait Publishable
{
    public function isPublished(): bool
    {
        return $this->status === PageStatus::Published;
    }

    public function publish(): void
    {
        $data = ['status' => PageStatus::Published];

        if (array_key_exists('published_at', $this->getAttributes()) || $this->hasColumn('published_at')) {
            $data['published_at'] = now();
        }

        $this->update($data);
    }

    public function unpublish(): void
    {
        $data = ['status' => PageStatus::Draft];

        if (array_key_exists('published_at', $this->getAttributes()) || $this->hasColumn('published_at')) {
            $data['published_at'] = null;
        }

        $this->update($data);
    }

    /**
     * Check if the model's table has a given column.
     */
    protected function hasColumn(string $column): bool
    {
        return in_array($column, $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable()), true);
    }
}
