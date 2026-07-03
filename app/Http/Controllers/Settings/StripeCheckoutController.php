<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StripeCheckoutController extends Controller
{
    /**
     * Credit packs available for purchase.
     *
     * @var array<string, array{credits: int, amount_cents: int, name: string}>
     */
    public const PACKS = [
        'starter' => ['credits' => 100, 'amount_cents' => 500, 'name' => 'Starter (100 credits)'],
        'pro' => ['credits' => 500, 'amount_cents' => 2000, 'name' => 'Pro (500 credits)'],
    ];

    public function create(Request $request): RedirectResponse
    {
        $request->validate([
            'pack' => ['required', 'string', 'in:'.implode(',', array_keys(self::PACKS))],
        ]);

        $pack = self::PACKS[$request->input('pack')];
        $user = $request->user();

        $checkout = $user->checkout([
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => $pack['name'],
                ],
                'unit_amount' => $pack['amount_cents'],
            ],
            'quantity' => 1,
        ], [
            'metadata' => [
                'user_id' => $user->id,
                'credits' => $pack['credits'],
            ],
            'success_url' => route('billing.edit').'?checkout=success',
            'cancel_url' => route('billing.edit').'?checkout=cancelled',
        ]);

        return redirect($checkout->url);
    }
}
