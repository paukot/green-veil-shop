<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::inertia('/', 'Welcome')->name('home');
});
