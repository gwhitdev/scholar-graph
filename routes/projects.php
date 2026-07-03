<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\PaperController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PromptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('projects')->scopeBindings()->group(function () {
    Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::get('/{project}/papers/search', [PaperController::class, 'search'])->name('papers.search');
    Route::post('/{project}/papers', [PaperController::class, 'store'])->name('papers.store');
    Route::post('/{project}/papers/doi', [PaperController::class, 'storeByDoi'])->name('papers.doi.store');
    Route::post('/{project}/papers/{paper}/enrich', [PaperController::class, 'enrich'])->name('papers.enrich');
    Route::patch('/{project}/papers/{paper}', [PaperController::class, 'updateStatus'])->name('papers.status');
    Route::delete('/{project}/papers/{paper}', [PaperController::class, 'destroy'])->name('papers.destroy');

    Route::post('/{project}/chat', [ChatController::class, 'store'])->name('chat.store');

    Route::put('/{project}/prompt', [PromptController::class, 'update'])->name('projects.prompt.update');

    Route::get('/{project}/collections', [CollectionController::class, 'index'])->name('collections.index');
    Route::post('/{project}/collections', [CollectionController::class, 'store'])->name('collections.store');
    Route::patch('/{project}/collections/reorder', [CollectionController::class, 'reorder'])->name('collections.reorder');
    Route::patch('/{project}/collections/{collection}', [CollectionController::class, 'update'])->name('collections.update');
    Route::delete('/{project}/collections/{collection}', [CollectionController::class, 'destroy'])->name('collections.destroy');
    Route::post('/{project}/collections/{collection}/papers', [CollectionController::class, 'addPaper'])->name('collections.papers.add');
    Route::delete('/{project}/collections/{collection}/papers/{paper}', [CollectionController::class, 'removePaper'])->name('collections.papers.remove');
});
