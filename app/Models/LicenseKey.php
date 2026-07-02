<?php

namespace App\Models;

use Database\Factories\LicenseKeyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property int|null $plan_id
 * @property int|null $credits
 * @property int|null $redeemed_by
 * @property Carbon|null $redeemed_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['code', 'plan_id', 'credits', 'redeemed_by', 'redeemed_at', 'expires_at'])]
class LicenseKey extends Model
{
    /** @use HasFactory<LicenseKeyFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'redeemed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isRedeemed(): bool
    {
        return $this->redeemed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function redeemedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }
}
