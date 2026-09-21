<?php

namespace App\Livewire\Admin;

use App\Enums\PlanTier;
use App\Livewire\Admin\Concerns\ManagesUsers;
use App\Models\PaidAction;
use App\Models\QrCode;
use App\Models\User;
use App\Services\AdminMetricsService;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AdminUsers extends Component
{
    use ManagesUsers, WithPagination;

    /** Columns an admin may sort by, mapped to the select alias they use. */
    public const SORTS = [
        'created_at', 'name', 'email', 'qr_total_count', 'scans_total', 'paid_cents', 'last_qr_at',
    ];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $plan = 'all';

    #[Url(except: 'all')]
    public string $status = 'all';

    #[Url(except: 'all')]
    public string $flag = 'all';

    #[Url(except: 'created_at')]
    public string $sort = 'created_at';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    public int $perPage = 25;

    public function updating($property): void
    {
        if (in_array($property, ['search', 'plan', 'status', 'flag', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::SORTS, true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = $column === 'name' || $column === 'email' ? 'asc' : 'desc';
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'plan', 'status', 'flag');
        $this->resetPage();
    }

    public function exportCsv()
    {
        $this->assertAdmin();

        $metrics = app(AdminMetricsService::class);
        $rows = $this->usersQuery($metrics)->with('subscriptions')->get();
        $filename = 'users-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows, $metrics) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so accented names survive a double-click into Excel.
            fwrite($out, "\xEF\xBB\xBF");

            $this->writeCsvRow($out, [
                'id', 'name', 'email', 'plan', 'subscription_status', 'is_admin', 'email_verified',
                'dynamic_qr', 'dynamic_limit', 'static_qr', 'static_limit', 'total_qr', 'scans',
                'one_off_paid', 'joined_at', 'last_qr_at',
            ]);

            foreach ($rows as $user) {
                $limits = $metrics->userLimits($user);
                $subscription = $metrics->validSubscription($user);

                $this->writeCsvRow($out, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $limits['tier']->label(),
                    $metrics->subscriptionState($subscription),
                    $user->is_admin ? 'yes' : 'no',
                    $user->email_verified_at ? 'yes' : 'no',
                    $limits['dynamic_used'],
                    $limits['dynamic_limit'] ?? 'unlimited',
                    $limits['static_used'],
                    $limits['static_limit'] ?? 'unlimited',
                    $user->qr_total_count,
                    (int) $user->scans_total,
                    Money::format((int) $user->paid_cents),
                    optional($user->created_at)->toDateTimeString(),
                    $user->last_qr_at,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** PHP 8.4+ requires the escape argument; an empty one keeps the output RFC 4180 clean. */
    protected function writeCsvRow($handle, array $fields): void
    {
        fputcsv($handle, $fields, ',', '"', '');
    }

    protected function usersQuery(AdminMetricsService $metrics): Builder
    {
        $query = User::query()
            ->select('users.*')
            ->withCount([
                'qrCodes as qr_total_count',
                'qrCodes as qr_dynamic_count' => fn (Builder $q) => $q->where('is_dynamic', true),
                'qrCodes as qr_static_count' => fn (Builder $q) => $q->where('is_dynamic', false),
            ])
            ->withSum('qrCodes as scans_total', 'total_scans')
            ->addSelect([
                'last_qr_at' => QrCode::select('created_at')
                    ->whereColumn('qr_codes.user_id', 'users.id')
                    ->orderByDesc('created_at')
                    ->limit(1),
                'paid_cents' => PaidAction::selectRaw('COALESCE(SUM(amount_cents), 0)')
                    ->whereColumn('paid_actions.user_id', 'users.id')
                    ->where('status', 'completed'),
            ]);

        if ($this->search !== '') {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $this->search).'%';

            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
            });
        }

        if ($this->plan !== 'all' && $tier = PlanTier::tryFrom($this->plan)) {
            $metrics->applyTierFilter($query, $tier);
        }

        $this->applyStatusFilter($query);
        $this->applyFlagFilter($query, $metrics);

        $sort = in_array($this->sort, self::SORTS, true) ? $this->sort : 'created_at';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderBy('users.id', 'desc');
    }

    protected function applyStatusFilter(Builder $query): void
    {
        match ($this->status) {
            'free' => $query->whereDoesntHave('subscriptions', fn (Builder $q) => $q->where('type', 'default')->active()),
            'active' => $query->whereHas('subscriptions', fn (Builder $q) => $q->where('type', 'default')
                ->active()
                ->whereNull('ends_at')
                ->where(fn (Builder $w) => $w->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', now()))),
            'trialing' => $query->whereHas('subscriptions', fn (Builder $q) => $q->where('type', 'default')->onTrial()),
            'grace' => $query->whereHas('subscriptions', fn (Builder $q) => $q->where('type', 'default')->onGracePeriod()),
            'canceled' => $query->whereHas('subscriptions', fn (Builder $q) => $q->where('type', 'default')
                ->whereNotNull('ends_at')
                ->where('ends_at', '<=', now())),
            default => null,
        };
    }

    protected function applyFlagFilter(Builder $query, AdminMetricsService $metrics): void
    {
        match ($this->flag) {
            'admins' => $query->where('is_admin', true),
            'unverified' => $query->whereNull('email_verified_at'),
            'at_limit' => $metrics->applyAtDynamicLimitFilter($query),
            'deletion' => $query->whereNotNull('account_deletion_scheduled_at'),
            default => null,
        };
    }

    public function render()
    {
        $metrics = app(AdminMetricsService::class);

        $users = $this->usersQuery($metrics)
            ->with('subscriptions')
            ->paginate($this->perPage);

        $rows = collect($users->items())->map(function (User $user) use ($metrics) {
            $subscription = $metrics->validSubscription($user);

            return [
                'user' => $user,
                'limits' => $metrics->userLimits($user),
                'state' => $metrics->subscriptionState($subscription),
                'comped' => $metrics->isComped($subscription),
            ];
        });

        return view('livewire.admin.admin-users', [
            'users' => $users,
            'rows' => $rows,
            'planOptions' => PlanTier::cases(),
        ])->layout('layouts.app', ['title' => __('admin.users')]);
    }
}
