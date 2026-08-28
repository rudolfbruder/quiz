<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/topic.php';
require __DIR__.'/source.php';
require __DIR__.'/generation.php';
require __DIR__.'/quiz.php';
require __DIR__.'/attempt.php';
require __DIR__.'/review.php';
