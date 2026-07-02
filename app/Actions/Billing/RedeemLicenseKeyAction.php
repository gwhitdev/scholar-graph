<?php

namespace App\Actions\Billing;

use App\Models\LicenseKey;
use App\Models\User;
use App\Services\Billing\CreditService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RedeemLicenseKeyAction
{
    public function __construct(
        protected CreditService $creditService,
    ) {}

    /**
     * @throws RuntimeException
     */
    public function handle(User $user, string $code): LicenseKey
    {
        $key = LicenseKey::where('code', $code)->first();

        if (! $key) {
            throw new RuntimeException('Invalid licence key.');
        }

        if ($key->isRedeemed()) {
            throw new RuntimeException('This licence key has already been redeemed.');
        }

        if ($key->isExpired()) {
            throw new RuntimeException('This licence key has expired.');
        }

        DB::transaction(function () use ($user, $key) {
            $key->update([
                'redeemed_by' => $user->id,
                'redeemed_at' => now(),
            ]);

            if ($key->plan_id) {
                $user->update([
                    'plan_id' => $key->plan_id,
                    'plan_expires_at' => now()->addYear(),
                ]);
            }

            if ($key->credits) {
                $this->creditService->grant($user, $key->credits, 'license_redeem', [
                    'license_key_id' => $key->id,
                ]);
            }
        });

        return $key->fresh();
    }
}
