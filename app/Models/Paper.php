<?php

namespace App\Models;

use Database\Factories\PaperFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paper extends Model
{
    /** @use HasFactory<PaperFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'openalex_id',
        'title',
        'abstract',
        'year',
        'authors',
        'doi',
        'venue',
        'pages',
        'cited_by_count',
        'referenced_works',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'authors' => 'array',
            'referenced_works' => 'array',
        ];
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_papers')
            ->withPivot(['user_id', 'status', 'added_at'])
            ->withTimestamps();
    }

    /**
     * @return HasOne<PaperEnrichment, $this>
     */
    public function enrichment(): HasOne
    {
        return $this->hasOne(PaperEnrichment::class);
    }
}
