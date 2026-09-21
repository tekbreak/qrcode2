<?php

namespace App\Livewire\Admin;

use App\Enums\PlanTier;
use App\Livewire\Admin\Concerns\ManagesUsers;
use App\Models\PaidAction;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\AdminMetricsService;
use Livewire\Component;
use Livewire\WithPagination;

class AdminUserDetail extends Component
{
    use ManagesUsers, WithPagination;

    public User $user;

    /** Plan slug staged in the "change plan" control. */
    public string $newPlan = '';

    public bool $newPlanYearly = false;

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->newPlan = $user->planTier()->value;
    }

    public function applyPlanChange(): void
    {
        $this->changePlan($this->user->id, $this->newPlan, $this->newPlanYearly);

        $this->user->refresh();
        $this->newPlan = $this->user->planTier()->value;
    }

    public function cancelUserSubscription(): void
    {
        $this->cancelSubscription($this->user->id);

        $this->user->refresh();
        $this->newPlan = $this->user->planTier()->value;
    }

    public function toggleUserAdmin(): void
    {
        $this->toggleAdmin($this->user->id);

        $this->user->refresh();
    }

    protected function afterUserDeleted()
    {
        return $this->redirect(route('admin.users'), navigate: true);
    }

    public function render()
    {
        $metrics = app(AdminMetricsService::class);
        $deletions = app(AccountDeletionService::class);

        $this->user->loadMissing('subscriptions');
        $this->user->loadCount([
            'qrCodes as qr_total_count',
            'qrCodes as qr_dynamic_count' => fn ($q) => $q->where('is_dynamic', true),
            'qrCodes as qr_static_count' => fn ($q) => $q->where('is_dynamic', false),
        ]);

        $subscription = $metrics->validSubscription($this->user);

        $subscriptionRows = $this->user->subscriptions
            ->sortByDesc('created_at')
            ->map(fn ($s) => [
                'subscription' => $s,
                'tier' => $metrics->tierForPriceId($s->stripe_price),
                'state' => $metrics->subscriptionState($s),
                'interval' => $metrics->billingInterval($s),
                'monthly_cents' => $metrics->monthlyCents($s),
                'comped' => $metrics->isComped($s),
            ])
            ->values();

        return view('livewire.admin.admin-user-detail', [
            'limits' => $metrics->userLimits($this->user),
            'subscription' => $subscription,
            'subscriptionState' => $metrics->subscriptionState($subscription),
            'subscriptionRows' => $subscriptionRows,
            'paidActions' => PaidAction::where('user_id', $this->user->id)
                ->orderByDesc('created_at')
                ->limit(25)
                ->get(),
            'lifetimeCents' => $metrics->userLifetimeValueCents($this->user),
            'scanTotals' => $metrics->userScanTotals($this->user),
            'dailyScans' => $metrics->userDailyScans($this->user, 30),
            'qrCodes' => $this->user->qrCodes()
                ->with('shortLink')
                ->orderByDesc('total_scans')
                ->paginate(10),
            'teamsOwned' => $this->user->ownedTeams()->withCount('users')->get(),
            'teamsMember' => $this->user->teams()->get(),
            'deletionDueAt' => $deletions->deletionDueAt($this->user),
            'planOptions' => PlanTier::cases(),
            'stripeUrl' => filled($this->user->stripe_id) && str_starts_with((string) $this->user->stripe_id, 'cus_')
                ? 'https://dashboard.stripe.com/customers/'.$this->user->stripe_id
                : null,
        ])->layout('layouts.app', ['title' => $this->user->name]);
    }
}
