<?php

use App\Http\Controllers\HelpController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('help')->name('help.')->group(function () {
    Route::get('/', [HelpController::class, 'index'])->name('index');
    Route::get('/search', [HelpController::class, 'search'])->name('search');
    Route::get('/{category:slug}/{article:slug}', [HelpController::class, 'show'])->name('show');
});
