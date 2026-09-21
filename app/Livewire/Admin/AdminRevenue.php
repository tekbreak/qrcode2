<?php

namespace App\Livewire\Admin;

use App\Models\PaidAction;
use App\Services\AdminMetricsService;
use Laravel\Cashier\Subscription;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AdminRevenue extends Component
{
    use WithPagination;

    #[Url(except: 'all')]
    public string $subscriptionStatus = 'all';

    #[Url(except: 'all')]
    public string $actionStatus = 'all';

    public function updating($property): void
    {
        if (in_array($property, ['subscriptionStatus', 'actionStatus'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $metrics = app(AdminMetricsService::class);

        $subscriptions = Subscription::query()
            ->with('user:id,name,email')
            ->where('type', 'default')
            ->when($this->subscriptionStatus === 'active', fn ($q) => $q->active())
            ->when($this->subscriptionStatus === 'trialing', fn ($q) => $q->onTrial())
            ->when($this->subscriptionStatus === 'canceled', fn ($q) => $q->whereNotNull('ends_at'))
            ->orderByDesc('created_at')
            ->paginate(15, ['*'], 'subsPage');

        $subscriptionRows = collect($subscriptions->items())->map(fn (Subscription $s) => [
            'subscription' => $s,
            'tier' => $metrics->tierForPriceId($s->stripe_price),
            'state' => $metrics->subscriptionState($s),
            'interval' => $metrics->billingInterval($s),
            'monthly_cents' => $metrics->monthlyCents($s),
            'comped' => $metrics->isComped($s),
        ]);

        $paidActions = PaidAction::query()
            ->with('user:id,name,email')
            ->when($this->actionStatus !== 'all', fn ($q) => $q->where('status', $this->actionStatus))
            ->orderByDesc('created_at')
            ->paginate(15, ['*'], 'actionsPage');

        return view('livewire.admin.admin-revenue', [
            'stats' => $metrics->revenueStats(),
            'mrrByTier' => $metrics->mrrByTier(),
            'newSubscriptions' => $metrics->monthlyNewSubscriptions(12),
            'oneOffRevenue' => $metrics->monthlyOneOffRevenue(12),
            'subscriptions' => $subscriptions,
            'subscriptionRows' => $subscriptionRows,
            'paidActions' => $paidActions,
        ])->layout('layouts.app', ['title' => __('admin.revenue')]);
    }
}
