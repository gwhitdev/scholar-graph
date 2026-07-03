<?php

use App\Http\Controllers\Settings\BillingController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\PromptSettingsController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\StripeCheckoutController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::get('settings/prompt', [PromptSettingsController::class, 'edit'])->name('prompt.edit');
    Route::put('settings/prompt', [PromptSettingsController::class, 'update'])->name('prompt.update');

    Route::get('settings/billing', [BillingController::class, 'edit'])->name('billing.edit');
    Route::post('settings/billing/redeem', [BillingController::class, 'redeem'])->name('billing.redeem');
    Route::post('settings/billing/checkout', [StripeCheckoutController::class, 'create'])->name('billing.checkout');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
