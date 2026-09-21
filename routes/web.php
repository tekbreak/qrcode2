<?php

use App\Http\Controllers\Auth\ChoosePlanController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PaidActionController;
use App\Http\Controllers\SeoController;
use App\Livewire\Admin\AdminOverview;
use App\Livewire\Admin\AdminRevenue;
use App\Livewire\Admin\AdminUsage;
use App\Livewire\Admin\AdminUserDetail;
use App\Livewire\Admin\AdminUsers;
use App\Livewire\Analytics\AnalyticsIndex;
use App\Livewire\Auth\ChoosePlan;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Billing\BillingIndex;
use App\Livewire\Dashboard;
use App\Livewire\QrCodes\BulkGenerator;
use App\Livewire\QrCodes\CategoryIndex;
use App\Livewire\QrCodes\QrCodeBuilder;
use App\Livewire\QrCodes\QrCodeIndex;
use App\Livewire\Settings\SettingsIndex;
use App\Livewire\Teams\TeamManager;
use App\Services\ImpersonationService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', function () {
    return view('landing.index');
})->name('landing');

// Crawler files. Registered without a domain constraint so the short-link
// proxy domain gets the restrictive robots.txt from the same handler.
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

Route::post('/language/switch', [LanguageController::class, 'switch'])
    ->middleware('throttle:30,1')
    ->name('language.switch');

// Guest auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');

    Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

    Route::post('/auth/magic-link', [MagicLinkController::class, 'send'])
        ->middleware('throttle:5,10')
        ->name('auth.magic-link.send');
    Route::get('/auth/magic-link/{user}', [MagicLinkController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('auth.magic-link.verify');
});

Route::get('/choose-plan', ChoosePlan::class)->name('auth.choose-plan');
Route::post('/choose-plan', [ChoosePlanController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('auth.choose-plan.store');

// Authenticated routes (plan selection not required)
Route::middleware('auth')->group(function () {
    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/');
    })->name('logout');

    Route::get('/email/verify', VerifyEmail::class)->name('verification.notice');

    Route::post('/impersonate/stop', function (ImpersonationService $impersonation) {
        if (! $impersonation->stop()) {
            return back()->with('error', __('admin.impersonation_not_active'));
        }

        return redirect()->route('admin.users')->with('status', __('admin.impersonation_ended'));
    })->middleware('throttle:20,1')->name('impersonate.stop');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
});

// Authenticated routes (plan must be selected). Publishing surfaces also
// require a verified email address; billing and settings deliberately do not,
// so an unverified user can still correct their address or manage a plan.
Route::middleware(['auth', 'plan.selected'])->group(function () {
    Route::middleware('verified')->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');

        Route::get('/qr-codes', QrCodeIndex::class)->name('qr-codes.index');
        Route::get('/qr-codes/categories', CategoryIndex::class)->name('categories.index');
        Route::get('/qr-codes/create', QrCodeBuilder::class)->name('qr-codes.create');
        Route::get('/qr-codes/{qrCode}/edit', QrCodeBuilder::class)->name('qr-codes.edit');
        Route::get('/qr-codes/bulk', BulkGenerator::class)->name('qr-codes.bulk');

        Route::get('/analytics', AnalyticsIndex::class)->name('analytics.index');
        Route::get('/analytics/{qrCode}', AnalyticsIndex::class)->name('analytics.show');

        Route::get('/teams', TeamManager::class)->name('teams.index');
    });

    Route::get('/billing', BillingIndex::class)->name('billing.index');
    Route::get('/paid-actions/{paidAction}/success', [PaidActionController::class, 'success'])->name('paid-actions.success');
    Route::get('/paid-actions/{paidAction}/cancel', [PaidActionController::class, 'cancel'])->name('paid-actions.cancel');

    Route::get('/settings', SettingsIndex::class)->name('settings.index');

    // Admin routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/', AdminOverview::class)->name('admin.dashboard');
        Route::get('/users', AdminUsers::class)->name('admin.users');
        Route::get('/users/{user}', AdminUserDetail::class)->name('admin.users.show');
        Route::get('/revenue', AdminRevenue::class)->name('admin.revenue');
        Route::get('/usage', AdminUsage::class)->name('admin.usage');
    });
});
