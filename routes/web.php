<?php

use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

require __DIR__.'/settings.php';
require __DIR__.'/projects.php';
require __DIR__.'/admin.php';
require __DIR__.'/help.php';
require __DIR__.'/support.php';

// Public CMS catch-all (MUST be last to avoid shadowing app routes)
Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('public.page');
