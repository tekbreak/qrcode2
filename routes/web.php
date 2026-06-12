<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PaidActionController;
use App\Livewire\Admin\AdminDashboard;
use App\Livewire\Analytics\AnalyticsIndex;
use App\Livewire\Auth\ChoosePlan;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Billing\BillingIndex;
use App\Livewire\Dashboard;
use App\Livewire\QrCodes\BulkGenerator;
use App\Livewire\QrCodes\QrCodeBuilder;
use App\Livewire\QrCodes\QrCodeIndex;
use App\Livewire\Settings\SettingsIndex;
use App\Livewire\Teams\TeamManager;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', function () {
    return view('landing.index');
})->name('landing');

Route::post('/language/switch', [LanguageController::class, 'switch'])->name('language.switch');

// Guest auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');

    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

    Route::post('/auth/magic-link', [MagicLinkController::class, 'send'])->name('auth.magic-link.send');
    Route::get('/auth/magic-link/{user}', [MagicLinkController::class, 'verify'])->name('auth.magic-link.verify');
});

Route::get('/choose-plan', ChoosePlan::class)->name('auth.choose-plan');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/');
    })->name('logout');

    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/qr-codes', QrCodeIndex::class)->name('qr-codes.index');
    Route::get('/qr-codes/create', QrCodeBuilder::class)->name('qr-codes.create');
    Route::get('/qr-codes/{qrCode}/edit', QrCodeBuilder::class)->name('qr-codes.edit');
    Route::get('/qr-codes/bulk', BulkGenerator::class)->name('qr-codes.bulk');

    Route::get('/analytics', AnalyticsIndex::class)->name('analytics.index');
    Route::get('/analytics/{qrCode}', AnalyticsIndex::class)->name('analytics.show');

    Route::get('/billing', BillingIndex::class)->name('billing.index');
    Route::get('/paid-actions/{paidAction}/success', [PaidActionController::class, 'success'])->name('paid-actions.success');
    Route::get('/paid-actions/{paidAction}/cancel', [PaidActionController::class, 'cancel'])->name('paid-actions.cancel');

    Route::get('/settings', SettingsIndex::class)->name('settings.index');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::get('/teams', TeamManager::class)->name('teams.index');

    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/', AdminDashboard::class)->name('admin.dashboard');
    });
});
