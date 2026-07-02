<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/usage', [AdminController::class, 'usage'])->name('usage.index');
    Route::get('/licenses', [AdminController::class, 'licenses'])->name('licenses.index');
    Route::post('/licenses', [AdminController::class, 'mintLicenseKeys'])->name('licenses.store');
});
