<?php

namespace App\Actions\Billing;

use App\Models\LicenseKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GenerateLicenseKeysAction
{
    /**
     * Generate N licence keys with random codes.
     *
     * @return Collection<int, LicenseKey>
     */
    public function handle(int $count, ?int $planId = null, ?int $credits = null, ?Carbon $expiresAt = null): Collection
    {
        $keys = new Collection;

        for ($i = 0; $i < $count; $i++) {
            $keys->push(LicenseKey::create([
                'code' => $this->generateCode(),
                'plan_id' => $planId,
                'credits' => $credits,
                'expires_at' => $expiresAt,
            ]));
        }

        return $keys;
    }

    protected function generateCode(): string
    {
        return strtoupper(
            Str::random(4).'-'.Str::random(4).'-'.Str::random(4).'-'.Str::random(4)
        );
    }
}
