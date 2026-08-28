<?php

declare(strict_types=1);

use App\Http\Controllers\Source\SourceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('sources', [SourceController::class, 'index'])->name('sources.index');
    Route::post('sources', [SourceController::class, 'store'])->name('sources.store');
    Route::post('sources/{source}/retry', [SourceController::class, 'retry'])->name('sources.retry');
    Route::delete('sources/{source}', [SourceController::class, 'destroy'])->name('sources.destroy');
});
