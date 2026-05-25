<?php

namespace App\Services;

use App\Enums\PlanTier;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Subscription;

class SubscriptionService
{
    public function subscribe(User $user, string $planSlug, bool $yearly = false): RedirectResponse|string
    {
        $user = $user->fresh();
        $plan = Plan::where('slug', $planSlug)->firstOrFail();

        if ($planSlug === 'free') {
            return $this->downgradeToFree($user);
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
                $this->syncCreditsForPlan($user);

                return 'swapped';
            }

            $this->applyDevSubscription($user, $plan, $yearly);

            return 'dev_applied';
        }

        if ($this->usesStripeCheckout($priceId)) {
            return $user->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('billing.index').'?success=1',
                    'cancel_url' => route('billing.index').'?cancelled=1',
                ])
                ->redirect();
        }

        $this->applyDevSubscription($user, $plan, $yearly);

        return 'dev_applied';
    }

    public function downgradeToFree(User $user): string
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

        $this->syncCreditsForPlan($user);

        return 'downgraded';
    }

    public function syncCreditsForPlan(User $user): void
    {
        $tier = $user->fresh()->planTier();

        if ($tier->hasUnlimitedCredits()) {
            return;
        }

        $allowance = $tier->monthlyCredits();
        $balance = $user->creditBalance;

        if (! $balance) {
            $user->createCreditBalance($tier);

            return;
        }

        $balance->update([
            'balance' => $allowance,
            'monthly_allowance' => $allowance,
            'resets_at' => now()->addMonth()->startOfMonth(),
        ]);
    }

    public function handleWebhook(WebhookHandled $event): void
    {
        $type = $event->payload['type'] ?? '';

        if (! in_array($type, [
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'checkout.session.completed',
        ], true)) {
            return;
        }

        $customerId = $this->extractCustomerId($event->payload);

        if (! $customerId) {
            return;
        }

        $user = Cashier::findBillable($customerId);

        if ($user instanceof User) {
            $this->syncCreditsForPlan($user);
        }
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

    protected function applyDevSubscription(User $user, Plan $plan, bool $yearly): void
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
        ]);

        $this->syncCreditsForPlan($user);
    }

    protected function extractCustomerId(array $payload): ?string
    {
        $object = $payload['data']['object'] ?? [];

        return $object['customer']
            ?? $object['customer_id']
            ?? null;
    }

    protected function defaultSubscription(User $user): ?Subscription
    {
        return $user->subscriptions()->where('type', 'default')->first();
    }
}
