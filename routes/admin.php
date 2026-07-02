<?php

use App\Http\Controllers\Admin\Cms\AdminMediaController;
use App\Http\Controllers\Admin\Cms\AdminPageController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/usage', [AdminController::class, 'usage'])->name('usage.index');
    Route::get('/licenses', [AdminController::class, 'licenses'])->name('licenses.index');
    Route::post('/licenses', [AdminController::class, 'mintLicenseKeys'])->name('licenses.store');

    // CMS Pages
    Route::resource('pages', AdminPageController::class)->except(['show']);
    Route::post('/pages/{page}/publish', [AdminPageController::class, 'publish'])->name('pages.publish');
    Route::post('/pages/{page}/unpublish', [AdminPageController::class, 'unpublish'])->name('pages.unpublish');

    // CMS Media
    Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
    Route::post('/media', [AdminMediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{medium}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
});
