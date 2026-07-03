<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Models\Concerns\Publishable;
use Database\Factories\HelpArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $help_category_id
 * @property string $slug
 * @property string $title
 * @property array<int, array<string, mixed>>|null $content
 * @property PageStatus $status
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['help_category_id', 'slug', 'title', 'content', 'status', 'sort'])]
class HelpArticle extends Model
{
    /** @use HasFactory<HelpArticleFactory> */
    use HasFactory, Publishable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'status' => PageStatus::class,
        ];
    }

    /**
     * @return BelongsTo<HelpCategory, $this>
     */
    public function helpCategory(): BelongsTo
    {
        return $this->belongsTo(HelpCategory::class, 'help_category_id');
    }
}
