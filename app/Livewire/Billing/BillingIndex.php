<?php

namespace App\Livewire\Billing;

use App\Models\Plan;
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
                    : 'You are now on the Starter plan.',
                default => 'Plan updated successfully.',
            };
        } catch (\Throwable $e) {
            report($e);
            $this->errorMessage = $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Unable to update your plan. Please try again or contact support.';
        }
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
        ])->layout('layouts.app', ['title' => __('nav.billing')]);
    }
}
