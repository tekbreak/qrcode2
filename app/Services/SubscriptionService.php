<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;

class SubscriptionService
{
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
                throw new \RuntimeException('You are already on this plan.');
            }

            if ($this->usesStripeCheckout($priceId)) {
                $subscription->swap($priceId);

                return 'swapped';
            }

            $this->applyDevSubscription($user, $plan, $yearly, $withTrial);

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

        $this->applyDevSubscription($user, $plan, $yearly, $withTrial);

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

    public function resolvePriceId(Plan $plan, bool $yearly): ?string
    {
        return $yearly ? $plan->stripe_yearly_price_id : $plan->stripe_monthly_price_id;
    }

    public function usesStripeCheckout(?string $priceId): bool
    {
        if (! $priceId || str_starts_with($priceId, 'dev_')) {
            return false;
        }

        return filled(config('cashier.secret')) && filled(config('cashier.key'));
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
}
