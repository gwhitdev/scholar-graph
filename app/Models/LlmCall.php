<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'model',
        'context_type',
        'context_id',
        'prompt',
        'prompt_tokens',
        'completion_tokens',
        'cost_usd',
        'duration_ms',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost_usd' => 'decimal:6',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
