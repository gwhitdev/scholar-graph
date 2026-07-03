<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Models\Concerns\Publishable;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property array<int, array<string, mixed>>|null $content
 * @property PageStatus $status
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string|null $og_image
 * @property Carbon|null $published_at
 * @property int|null $author_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['slug', 'title', 'content', 'status', 'seo_title', 'seo_description', 'og_image', 'published_at', 'author_id'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
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
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return HasMany<NavigationItem, $this>
     */
    public function navigationItems(): HasMany
    {
        return $this->hasMany(NavigationItem::class);
    }
}
