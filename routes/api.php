<?php

use App\Http\Controllers\Api\AnalyticsApiController;
use App\Http\Controllers\Api\QrCodeApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function () {
        return request()->user();
    });

    Route::apiResource('qr-codes', QrCodeApiController::class)->names('api.qr-codes');
    Route::get('/qr-codes/{qrCode}/download', [QrCodeApiController::class, 'download'])->name('api.qr-codes.download');
    Route::get('/qr-codes/{qrCode}/analytics', [AnalyticsApiController::class, 'stats'])->name('api.qr-codes.analytics');
});
