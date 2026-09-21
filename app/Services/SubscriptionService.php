<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;

class SubscriptionService
{
    /**
     * Prefixes used for subscription rows that have no Stripe counterpart:
     * `dev_` for local development, `admin_` for plans comped from the admin zone.
     */
    public const COMPED_PREFIXES = ['dev_', 'admin_'];

    public function __construct(
        protected AccountDeletionService $accountDeletionService,
    ) {}

    public static function isComped(?Subscription $subscription): bool
    {
        if (! $subscription) {
            return false;
        }

        foreach (self::COMPED_PREFIXES as $prefix) {
            if (str_starts_with((string) $subscription->stripe_id, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function subscribe(
        User $user,
        string $planSlug,
        bool $yearly = false,
        bool $withTrial = false,
        ?array $checkoutUrls = null,
    ): RedirectResponse|string {
        $user = $user->fresh();
        $plan = Plan::where('slug', $planSlug)->firstOrFail();

        if ($planSlug === 'starter' && $plan->price_monthly === 0) {
            return $this->downgradeToStarter($user);
        }

        $priceId = $this->resolvePriceId($plan, $yearly);

        if (! $priceId) {
            throw new \RuntimeException('This plan is not available for checkout yet.');
        }

        $subscription = $this->defaultSubscription($user);

        if ($subscription && $subscription->valid()) {
            if ($subscription->stripe_price === $priceId) {
                return 'already_subscribed';
            }

            if ($this->usesStripeCheckout($priceId)) {
                $subscription->swap($priceId);

                $this->clearDeletionScheduleIfActive($user);

                return 'swapped';
            }

            $this->guardDevBilling();
            $this->applyDevSubscription($user, $plan, $yearly, $withTrial);

            $this->clearDeletionScheduleIfActive($user);

            return 'dev_applied';
        }

        if ($this->usesStripeCheckout($priceId)) {
            $builder = $user->newSubscription('default', $priceId);

            if ($withTrial) {
                $builder->trialDays($this->trialDays());
            }

            return $builder->checkout([
                'success_url' => $checkoutUrls['success'] ?? route('billing.index').'?success=1',
                'cancel_url' => $checkoutUrls['cancel'] ?? route('billing.index').'?cancelled=1',
            ])->redirect();
        }

        $this->guardDevBilling();
        $this->applyDevSubscription($user, $plan, $yearly, $withTrial);

        $this->clearDeletionScheduleIfActive($user);

        return 'dev_applied';
    }

    public function downgradeToStarter(User $user): string
    {
        $user = $user->fresh();
        $subscription = $this->defaultSubscription($user);

        if ($subscription && $subscription->valid()) {
            if ($this->usesStripeCheckout($subscription->stripe_price)) {
                $subscription->cancel();
            } else {
                $subscription->items()->delete();
                $subscription->delete();
            }
        }

        return 'downgraded';
    }

    /**
     * Move a user onto another plan from the admin zone.
     *
     * A live Stripe subscription is swapped in Stripe so billing follows the
     * change. Anything else is comped locally — that is a deliberate admin
     * grant, unlike the self-service path which refuses to hand out free plans.
     */
    public function adminChangePlan(User $user, string $planSlug, bool $yearly = false): string
    {
        $user = $user->fresh();
        $actorId = auth()->id();

        if ($planSlug === 'starter') {
            $result = $this->downgradeToStarter($user);

            Log::warning('admin.plan.downgraded', [
                'admin_id' => $actorId,
                'user_id' => $user->id,
            ]);

            return $result;
        }

        $plan = Plan::where('slug', $planSlug)->firstOrFail();
        $priceId = $this->resolvePriceId($plan, $yearly);

        if (! $priceId) {
            throw new \RuntimeException("The {$plan->name} plan has no price configured.");
        }

        $subscription = $this->defaultSubscription($user);
        $isLiveStripe = $subscription && $subscription->valid() && ! self::isComped($subscription);

        if ($isLiveStripe) {
            if ($subscription->stripe_price === $priceId) {
                return 'already_subscribed';
            }

            if (! $this->usesStripeCheckout($priceId)) {
                throw new \RuntimeException(
                    'This user has a live Stripe subscription. Configure a Stripe price for '
                    ."{$plan->name} or change the plan from the Stripe dashboard."
                );
            }

            $subscription->swap($priceId);
            $this->clearDeletionScheduleIfActive($user);

            Log::warning('admin.plan.swapped', [
                'admin_id' => $actorId,
                'user_id' => $user->id,
                'price_id' => $priceId,
            ]);

            return 'swapped';
        }

        $this->applyCompedSubscription($user, $priceId);
        $this->clearDeletionScheduleIfActive($user);

        Log::warning('admin.plan.comped', [
            'admin_id' => $actorId,
            'user_id' => $user->id,
            'plan' => $plan->slug,
            'price_id' => $priceId,
        ]);

        return 'comped';
    }

    /**
     * Cancel from the admin zone. Stripe subscriptions run to the end of the
     * paid period; comped rows have no period, so they end straight away.
     */
    public function adminCancelSubscription(User $user): string
    {
        $subscription = $this->defaultSubscription($user->fresh());

        if (! $subscription || ! $subscription->valid()) {
            throw new \RuntimeException('This user has no active subscription.');
        }

        Log::warning('admin.subscription.canceled', [
            'admin_id' => auth()->id(),
            'user_id' => $user->id,
            'stripe_id' => $subscription->stripe_id,
        ]);

        if (self::isComped($subscription)) {
            $subscription->items()->delete();
            $subscription->delete();

            return 'canceled_now';
        }

        $subscription->cancel();

        return 'canceled_at_period_end';
    }

    protected function applyCompedSubscription(User $user, string $priceId): void
    {
        $user->subscriptions()->each(function ($subscription) {
            $subscription->items()->delete();
            $subscription->delete();
        });

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'admin_'.Str::random(16),
            'stripe_status' => 'active',
            'stripe_price' => $priceId,
            'quantity' => 1,
        ]);
    }

    public function resolvePriceId(Plan $plan, bool $yearly): ?string
    {
        return $yearly ? $plan->stripe_yearly_price_id : $plan->stripe_monthly_price_id;
    }

    public function usesStripeCheckout(?string $priceId): bool
    {
        if (! $priceId || str_starts_with($priceId, 'dev_')) {
            return false;
        }

        $secret = config('cashier.secret');
        $key = config('cashier.key');

        if (! filled($secret) || ! filled($key)) {
            return false;
        }

        // Stripe secret keys start with sk_; publishable keys (pk_) cannot create checkouts.
        if (! str_starts_with($secret, 'sk_')) {
            return false;
        }

        return true;
    }

    /**
     * Subscriptions may only be granted without a real payment in local
     * development and in the test suite. Anywhere else this is a misconfiguration
     * that would hand out paid plans for free, so fail loudly instead.
     */
    protected function guardDevBilling(): void
    {
        if (app()->environment('local', 'testing')) {
            return;
        }

        throw new \RuntimeException(__('auth.plan_payment_failed'));
    }

    protected function applyDevSubscription(User $user, Plan $plan, bool $yearly, bool $withTrial = false): void
    {
        $priceId = $this->resolvePriceId($plan, $yearly);

        $user->subscriptions()->each(function ($subscription) {
            $subscription->items()->delete();
            $subscription->delete();
        });

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'dev_'.Str::random(16),
            'stripe_status' => 'active',
            'stripe_price' => $priceId,
            'quantity' => 1,
            'trial_ends_at' => $withTrial ? now()->addDays($this->trialDays()) : null,
        ]);
    }

    protected function trialDays(): int
    {
        return (int) config('qrcode.signup_trial_days', 30);
    }

    protected function defaultSubscription(User $user): ?Subscription
    {
        return $user->subscriptions()->where('type', 'default')->first();
    }

    protected function clearDeletionScheduleIfActive(User $user): void
    {
        $user = $user->fresh();

        if ($user->account_deletion_scheduled_at && $user->subscribed('default')) {
            $this->accountDeletionService->clearScheduledDeletion($user);
        }
    }
}
