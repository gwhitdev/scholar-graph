<?php

use App\Http\Controllers\Admin\Cms\AdminMediaController;
use App\Http\Controllers\Admin\Cms\AdminPageController;
use App\Http\Controllers\Admin\Help\AdminHelpArticleController;
use App\Http\Controllers\Admin\Help\AdminHelpCategoryController;
use App\Http\Controllers\Admin\Support\AdminTicketController;
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

    // Help Categories
    Route::resource('help-categories', AdminHelpCategoryController::class)->except(['show']);

    // Help Articles
    Route::resource('help-articles', AdminHelpArticleController::class)->except(['show']);
    Route::post('/help-articles/{help_article}/publish', [AdminHelpArticleController::class, 'publish'])->name('help-articles.publish');
    Route::post('/help-articles/{help_article}/unpublish', [AdminHelpArticleController::class, 'unpublish'])->name('help-articles.unpublish');

    // Support Tickets
    Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('tickets.reply');
    Route::patch('/tickets/{ticket}/status', [AdminTicketController::class, 'updateStatus'])->name('tickets.status');
});
