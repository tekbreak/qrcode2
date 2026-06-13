<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Stripe\Exception\InvalidRequestException;
use Stripe\Subscription as StripeSubscription;

class SubscriptionSyncService
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
    ) {}

    public function canSyncWithStripe(): bool
    {
        return $this->subscriptionService->usesStripeCheckout('price_live_check');
    }

    public function isLiveStripeSubscription(Subscription $subscription): bool
    {
        return filled($subscription->stripe_id)
            && ! Str::startsWith($subscription->stripe_id, 'dev_');
    }

    /**
     * @return array{synced: int, canceled: int, deleted: int, skipped: int, errors: int}
     */
    public function syncUser(User $user): array
    {
        $counts = [
            'synced' => 0,
            'canceled' => 0,
            'deleted' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($user->subscriptions as $subscription) {
            $result = $this->syncSubscription($subscription);
            $counts[$result]++;
        }

        return $counts;
    }

    /**
     * @return 'synced'|'canceled'|'deleted'|'skipped'|'errors'
     */
    public function syncSubscription(Subscription $subscription): string
    {
        if (! $this->canSyncWithStripe()) {
            return 'skipped';
        }

        if (! $this->isLiveStripeSubscription($subscription)) {
            return 'skipped';
        }

        try {
            $stripeSubscription = $subscription->asStripeSubscription();

            if ($stripeSubscription->status === StripeSubscription::STATUS_INCOMPLETE_EXPIRED) {
                $subscription->items()->delete();
                $subscription->delete();

                return 'deleted';
            }

            $this->applyStripeSubscription($subscription, $stripeSubscription);

            return 'synced';
        } catch (InvalidRequestException $e) {
            if ($this->isMissingStripeSubscription($e)) {
                $subscription->skipTrial()->markAsCanceled();

                return 'canceled';
            }

            report($e);

            return 'errors';
        } catch (\Throwable $e) {
            report($e);

            return 'errors';
        }
    }

    protected function applyStripeSubscription(Subscription $subscription, StripeSubscription $stripeSubscription): void
    {
        $items = $stripeSubscription->items->data;
        $isSinglePrice = count($items) === 1;

        $subscription->stripe_status = $stripeSubscription->status;
        $subscription->stripe_price = $isSinglePrice ? $items[0]->price->id : null;
        $subscription->quantity = $isSinglePrice ? ($items[0]->quantity ?? null) : null;

        if ($stripeSubscription->trial_end) {
            $subscription->trial_ends_at = Carbon::createFromTimestamp($stripeSubscription->trial_end);
        }

        if ($stripeSubscription->cancel_at_period_end) {
            $subscription->ends_at = $subscription->onTrial()
                ? $subscription->trial_ends_at
                : Carbon::createFromTimestamp($stripeSubscription->current_period_end);
        } elseif ($stripeSubscription->cancel_at || $stripeSubscription->canceled_at) {
            $subscription->ends_at = Carbon::createFromTimestamp(
                $stripeSubscription->cancel_at ?? $stripeSubscription->canceled_at
            );
        } else {
            $subscription->ends_at = null;
        }

        $subscription->save();

        if ($items !== []) {
            $subscriptionItemIds = [];

            foreach ($items as $item) {
                $subscriptionItemIds[] = $item->id;

                $subscription->items()->updateOrCreate(
                    ['stripe_id' => $item->id],
                    [
                        'stripe_product' => $item->price->product,
                        'stripe_price' => $item->price->id,
                        'quantity' => $item->quantity ?? null,
                    ],
                );
            }

            $subscription->items()->whereNotIn('stripe_id', $subscriptionItemIds)->delete();
        }
    }

    protected function isMissingStripeSubscription(InvalidRequestException $exception): bool
    {
        $message = Str::lower($exception->getMessage());

        return Str::contains($message, ['no such subscription', 'resource_missing']);
    }
}
