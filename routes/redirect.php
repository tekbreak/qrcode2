<?php

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/{slug}', [RedirectController::class, 'handle'])
    ->where('slug', '[a-zA-Z0-9_-]+')
    ->name('redirect.handle');
