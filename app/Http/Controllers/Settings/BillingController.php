<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Billing\RedeemLicenseKeyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\RedeemLicenseKeyRequest;
use App\Services\Billing\CreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class BillingController extends Controller
{
    public function edit(Request $request, CreditService $creditService): Response
    {
        $user = $request->user()->load('plan');

        return Inertia::render('settings/billing', [
            'plan' => $user->plan,
            'balance' => $creditService->balance($user),
            'transactions' => $user->creditTransactions()->latest()->limit(20)->get(),
        ]);
    }

    public function redeem(RedeemLicenseKeyRequest $request, RedeemLicenseKeyAction $action): RedirectResponse
    {
        try {
            $action->handle($request->user(), $request->validated('code'));

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Licence key redeemed successfully.']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return to_route('billing.edit');
    }
}
