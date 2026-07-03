<?php

namespace App\Models;

use Database\Factories\HelpCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['slug', 'title', 'sort'])]
class HelpCategory extends Model
{
    /** @use HasFactory<HelpCategoryFactory> */
    use HasFactory;

    /**
     * @return HasMany<HelpArticle, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(HelpArticle::class);
    }
}
