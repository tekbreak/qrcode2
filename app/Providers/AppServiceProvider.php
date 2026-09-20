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
     * Stripe configuration gaps, for `php artisan billing:check`.
     *
     * Deliberately not called during boot. None of these expose the application:
     * SubscriptionService::guardDevBilling() refuses to grant a plan locally
     * outside local/testing, PaidActionService refuses to start an unpriced paid
     * action, and the webhook route has VerifyWebhookSignature attached
     * unconditionally so it rejects unsigned events with or without a secret.
     * They disable billing features, which is an operator question, not a
     * per-request one - and boot() runs on every request and every artisan
     * command, so checking here would only produce noise.
     *
     * @return array<int, string>
     */
    public static function billingConfigProblems(): array
    {
        $problems = [];
        $secret = config('cashier.secret');

        if (blank($secret)) {
            $problems[] = 'STRIPE_SECRET is not set; checkout and subscription sync are disabled.';
        } elseif (! str_starts_with($secret, 'sk_')) {
            $problems[] = 'STRIPE_SECRET does not look like a secret key (expected an "sk_" prefix).';
        }

        if (blank(config('cashier.key'))) {
            $problems[] = 'STRIPE_KEY is not set.';
        }

        if (blank(config('qrcode.paid_action_stripe_price_id'))) {
            $problems[] = 'STRIPE_PAID_ACTION_PRICE_ID is not set; paid actions are refused.';
        }

        // Not a security issue - the webhook rejects unsigned events regardless -
        // but without it, subscription changes made in the Stripe billing portal
        // are only picked up by the daily subscriptions:sync run.
        if (blank(config('cashier.webhook.secret'))) {
            $problems[] = 'STRIPE_WEBHOOK_SECRET is not set; billing state relies on the daily subscriptions:sync reconciliation.';
        }

        return $problems;
    }
}
