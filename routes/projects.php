<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\PaperController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PromptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('projects')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::get('/{project}/papers/search', [PaperController::class, 'search'])->name('papers.search');
    Route::post('/{project}/papers', [PaperController::class, 'store'])->name('papers.store');
    Route::delete('/{project}/papers/{paper}', [PaperController::class, 'destroy'])->name('papers.destroy');

    Route::post('/{project}/chat', [ChatController::class, 'store'])->name('chat.store');

    Route::put('/{project}/prompt', [PromptController::class, 'update'])->name('projects.prompt.update');
});
