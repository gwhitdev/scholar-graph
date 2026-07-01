<?php

namespace App\Models;

use Database\Factories\PaperEnrichmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperEnrichment extends Model
{
    /** @use HasFactory<PaperEnrichmentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'paper_id',
        'semantic_scholar_id',
        'tldr',
        'influential_citation_count',
        'related_paper_ids',
        'enriched_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'related_paper_ids' => 'array',
            'enriched_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Paper, $this>
     */
    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }
}
