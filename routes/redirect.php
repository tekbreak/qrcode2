<?php

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/{slug}', [RedirectController::class, 'handle'])
    ->where('slug', '[a-zA-Z0-9_-]+')
    ->middleware('throttle:redirect')
    ->name('redirect.handle');

Route::post('/{slug}', [RedirectController::class, 'unlock'])
    ->where('slug', '[a-zA-Z0-9_-]+')
    ->middleware('throttle:link-unlock')
    ->name('redirect.unlock');
