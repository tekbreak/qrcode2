<?php

namespace App\Providers;

use App\Listeners\SyncSubscriptionPlanCredits;
use App\Models\QrCode;
use App\Policies\QrCodePolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Events\WebhookHandled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(QrCode::class, QrCodePolicy::class);

        Event::listen(WebhookHandled::class, SyncSubscriptionPlanCredits::class);
    }
}
