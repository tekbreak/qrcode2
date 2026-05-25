<?php

namespace App\Livewire\Billing;

use App\Models\Plan;
use App\Services\CreditService;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Livewire\Component;

class BillingIndex extends Component
{
    public bool $yearly = false;
    public string $successMessage = '';
    public string $errorMessage = '';

    public function mount(): void
    {
        if (request()->boolean('success')) {
            app(SubscriptionService::class)->syncCreditsForPlan(auth()->user());
            $this->successMessage = 'Subscription updated successfully!';
        }

        if (request()->boolean('cancelled')) {
            $this->errorMessage = 'Checkout was cancelled.';
        }

        if (session('success')) {
            $this->successMessage = session('success');
        }

        if (session('error')) {
            $this->errorMessage = session('error');
        }
    }

    public function subscribe(string $planSlug)
    {
        $this->reset('successMessage', 'errorMessage');

        try {
            $result = app(SubscriptionService::class)->subscribe(
                auth()->user()->fresh(),
                $planSlug,
                $this->yearly
            );

            if ($result instanceof RedirectResponse) {
                return $this->redirect($result->getTargetUrl(), navigate: false);
            }

            $this->successMessage = match ($result) {
                'dev_applied' => 'Plan updated successfully (dev mode — no charge).',
                'swapped' => 'Your plan has been updated.',
                'downgraded' => auth()->user()->subscribed()
                    ? 'Subscription cancelled. You will keep access until the end of your billing period.'
                    : 'You are now on the Free plan.',
                default => 'Plan updated successfully.',
            };
        } catch (\Throwable $e) {
            report($e);
            $this->errorMessage = $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Unable to update your plan. Please try again or contact support.';
        }
    }

    public function purchaseCredits(string $packSlug)
    {
        $this->reset('successMessage', 'errorMessage');

        $user = auth()->user();
        $packs = collect(config('qrcode.credit_packs'));
        $pack = $packs->firstWhere('slug', $packSlug);

        if (! $pack) {
            $this->errorMessage = 'Invalid credit pack.';
            return;
        }

        if ($pack['stripe_price_id']) {
            try {
                $checkout = $user->checkout([$pack['stripe_price_id'] => 1], [
                    'success_url' => route('credits.purchase.success').'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('billing.index').'?cancelled=1',
                    'metadata' => [
                        'credit_pack' => $pack['slug'],
                        'credits' => $pack['credits'],
                        'user_id' => $user->id,
                    ],
                ]);

                return $this->redirect($checkout->asStripeCheckoutSession()->url, navigate: false);
            } catch (\Throwable $e) {
                report($e);
                $this->errorMessage = 'Unable to start checkout. Please verify your Stripe configuration.';
                return;
            }
        }

        $creditService = app(CreditService::class);
        $creditService->credit(
            $user,
            \App\Enums\CreditAction::CreditPurchase,
            $pack['credits'],
            "Purchased {$pack['credits']} credits ({$pack['slug']}) [dev mode]",
            ['pack' => $pack['slug'], 'dev_mode' => true]
        );

        $this->successMessage = "Added {$pack['credits']} credits to your account (dev mode — no charge).";
    }

    public function manageBilling()
    {
        try {
            return auth()->user()->redirectToBillingPortal(route('billing.index'));
        } catch (\Throwable $e) {
            report($e);
            $this->errorMessage = 'Billing portal is unavailable. Please configure Stripe first.';
        }
    }

    public function render()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        $user = auth()->user();

        return view('livewire.billing.billing-index', [
            'plans' => $plans,
            'currentTier' => $user->planTier(),
            'isSubscribed' => $user->subscribed(),
            'creditPacks' => config('qrcode.credit_packs'),
        ])->layout('layouts.app', ['title' => __('nav.billing')]);
    }
}
