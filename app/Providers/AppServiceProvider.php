<?php

namespace App\Providers;

use App\Models\QrCode;
use App\Models\Team;
use App\Policies\QrCodePolicy;
use App\Policies\TeamPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Cashier only attaches signature verification to its webhook route when
        // a secret happens to be configured. Register the routes here instead so
        // verification is unconditional.
        Cashier::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(QrCode::class, QrCodePolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);

        $this->assertBillingConfigured();
        $this->registerCashierRoutes();
    }

    /**
     * Cashier's own routes, with webhook signature verification always applied.
     */
    protected function registerCashierRoutes(): void
    {
        Route::group([
            'prefix' => config('cashier.path'),
            'namespace' => 'Laravel\Cashier\Http\Controllers',
            'as' => 'cashier.',
        ], function () {
            Route::get('payment/{id}', 'PaymentController@show')->name('payment');

            Route::post('webhook', 'WebhookController@handleWebhook')
                ->middleware([VerifyWebhookSignature::class, 'throttle:60,1'])
                ->name('webhook');
        });
    }

    /**
     * Billing controls in this application fail open when Stripe is not
     * configured: plans get granted locally and the webhook stops verifying
     * signatures. Refuse to boot a production environment in that state so a
     * missing environment variable is a loud deploy failure rather than a quiet
     * hole.
     */
    protected function assertBillingConfigured(): void
    {
        if (! $this->app->isProduction()) {
            return;
        }

        $secret = config('cashier.secret');

        if (blank($secret) || ! str_starts_with($secret, 'sk_')) {
            throw new \RuntimeException(
                'STRIPE_SECRET is missing or malformed; paid plans would be granted without payment.'
            );
        }

        if (blank(config('cashier.key'))) {
            throw new \RuntimeException('STRIPE_KEY is not set.');
        }

        if (blank(config('cashier.webhook.secret'))) {
            throw new \RuntimeException(
                'STRIPE_WEBHOOK_SECRET is not set; the Stripe webhook would accept unsigned events.'
            );
        }

        // STRIPE_PAID_ACTION_PRICE_ID is deliberately not asserted here: a
        // missing price id disables paid actions (PaidActionService fails closed)
        // rather than compromising the application, so it must not take the whole
        // site down.
    }
}
