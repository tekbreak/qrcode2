<?php

namespace App\Providers;

use App\Models\QrCode;
use App\Models\Team;
use App\Policies\QrCodePolicy;
use App\Policies\TeamPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
     * Report billing misconfiguration without taking the site down.
     *
     * Every way a missing Stripe variable could actually cost money is already
     * closed at the point of use: SubscriptionService::guardDevBilling() refuses
     * to grant a plan locally outside local/testing, PaidActionService refuses to
     * start an unpriced paid action, and the webhook route has
     * VerifyWebhookSignature attached unconditionally so it rejects unsigned
     * events whether or not a secret is configured.
     *
     * That makes a hard failure here pure downside: this application also serves
     * every customer's QR redirect, so aborting the boot over a billing variable
     * would take working short links offline. Log it loudly instead, and use
     * `php artisan billing:check` for a deploy-time gate.
     */
    protected function assertBillingConfigured(): void
    {
        if (! $this->app->isProduction()) {
            return;
        }

        foreach (static::billingConfigProblems() as $problem) {
            Log::error('Billing configuration: '.$problem);
        }
    }

    /**
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
