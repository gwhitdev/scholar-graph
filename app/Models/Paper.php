<?php

namespace App\Models;

use Database\Factories\PaperFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'project_id',
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
        'added_at',
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
            'added_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasOne<PaperEnrichment, $this>
     */
    public function enrichment(): HasOne
    {
        return $this->hasOne(PaperEnrichment::class);
    }
}
