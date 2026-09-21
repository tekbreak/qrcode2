<?php

namespace App\Services;

use App\Enums\PlanTier;
use App\Models\Category;
use App\Models\CustomDomain;
use App\Models\PaidAction;
use App\Models\Plan;
use App\Models\QrCode;
use App\Models\Scan;
use App\Models\ShortLink;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Subscription;

/**
 * Read-side queries for the admin zone.
 *
 * Plan resolution deliberately mirrors User::planTier(): a user is on a paid
 * tier only while they hold a valid (Cashier "active") subscription whose
 * stripe_price belongs to that plan. Anything else counts as Starter.
 */
class AdminMetricsService
{
    /** @var Collection<int, Plan>|null */
    protected ?Collection $plans = null;

    /*
    |--------------------------------------------------------------------------
    | Plan resolution
    |--------------------------------------------------------------------------
    */

    /** @return Collection<int, Plan> */
    public function plans(): Collection
    {
        return $this->plans ??= Plan::orderBy('sort_order')->get();
    }

    public function planForSlug(string $slug): ?Plan
    {
        return $this->plans()->firstWhere('slug', $slug);
    }

    /** @return array<int, string> */
    public function priceIdsForSlug(string $slug): array
    {
        $plan = $this->planForSlug($slug);

        if (! $plan) {
            return [];
        }

        return array_values(array_filter([
            $plan->stripe_monthly_price_id,
            $plan->stripe_yearly_price_id,
        ]));
    }

    /** Every price id that grants a paid tier. */
    public function paidPriceIds(): array
    {
        return array_merge(
            $this->priceIdsForSlug(PlanTier::Pro->value),
            $this->priceIdsForSlug(PlanTier::Enterprise->value),
        );
    }

    public function tierForPriceId(?string $priceId): PlanTier
    {
        if (! $priceId) {
            return PlanTier::Starter;
        }

        $plan = $this->plans()->first(
            fn (Plan $plan) => $plan->stripe_monthly_price_id === $priceId
                || $plan->stripe_yearly_price_id === $priceId
        );

        return $plan ? (PlanTier::tryFrom($plan->slug) ?? PlanTier::Starter) : PlanTier::Starter;
    }

    /**
     * Resolve a user's tier from an already loaded `subscriptions` relation,
     * so a table of users costs one query instead of one per row.
     */
    public function tierForLoadedUser(User $user): PlanTier
    {
        return $this->tierForPriceId($this->validSubscription($user)?->stripe_price);
    }

    public function validSubscription(User $user): ?Subscription
    {
        return $user->relationLoaded('subscriptions')
            ? $user->subscriptions->first(fn (Subscription $s) => $s->type === 'default' && $s->valid())
            : $user->subscriptions()->where('type', 'default')->get()->first(fn (Subscription $s) => $s->valid());
    }

    /** Human label for the state of a user's subscription. */
    public function subscriptionState(?Subscription $subscription): string
    {
        if (! $subscription) {
            return 'free';
        }

        if ($subscription->onTrial()) {
            return 'trialing';
        }

        if ($subscription->onGracePeriod()) {
            return 'grace';
        }

        if ($subscription->canceled() || $subscription->ended()) {
            return 'canceled';
        }

        return 'active';
    }

    public function isComped(?Subscription $subscription): bool
    {
        return SubscriptionService::isComped($subscription);
    }

    public function billingInterval(?Subscription $subscription): ?string
    {
        if (! $subscription?->stripe_price) {
            return null;
        }

        $plan = $this->plans()->first(
            fn (Plan $plan) => in_array($subscription->stripe_price, [
                $plan->stripe_monthly_price_id,
                $plan->stripe_yearly_price_id,
            ], true)
        );

        if (! $plan) {
            return null;
        }

        return $subscription->stripe_price === $plan->stripe_yearly_price_id ? 'yearly' : 'monthly';
    }

    /** Monthly-equivalent price of a subscription, in cents. */
    public function monthlyCents(?Subscription $subscription): int
    {
        if (! $subscription?->stripe_price) {
            return 0;
        }

        $plan = $this->plans()->first(
            fn (Plan $plan) => in_array($subscription->stripe_price, [
                $plan->stripe_monthly_price_id,
                $plan->stripe_yearly_price_id,
            ], true)
        );

        if (! $plan) {
            return 0;
        }

        return $subscription->stripe_price === $plan->stripe_yearly_price_id
            ? (int) round(((int) $plan->price_yearly) / 12)
            : (int) $plan->price_monthly;
    }

    /*
    |--------------------------------------------------------------------------
    | Query constraints shared by the user list
    |--------------------------------------------------------------------------
    */

    /** Subscriptions that currently grant access. */
    public function scopeValidSubscriptions(Builder $query): Builder
    {
        return $query->where('type', 'default')->active();
    }

    /**
     * Subscriptions that will actually bill again — the basis for MRR.
     *
     * Comped rows are excluded: an admin grant (or a local dev subscription)
     * carries a plan price but collects nothing, so counting it as revenue
     * would overstate MRR. They are surfaced separately as a comped count.
     */
    public function scopeRecurringPaid(Builder $query): Builder
    {
        $query->where('type', 'default')
            ->where('stripe_status', 'active')
            ->whereNull('ends_at')
            ->where(fn (Builder $q) => $q->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', now()))
            ->whereIn('stripe_price', $this->paidPriceIds() ?: ['__none__']);

        foreach (SubscriptionService::COMPED_PREFIXES as $prefix) {
            $query->where('stripe_id', 'not like', $prefix.'%');
        }

        return $query;
    }

    /** Active subscriptions granted without payment. */
    public function compedSubscriptionCount(): int
    {
        return $this->scopeValidSubscriptions(Subscription::query())
            ->where(function (Builder $query) {
                foreach (SubscriptionService::COMPED_PREFIXES as $prefix) {
                    $query->orWhere('stripe_id', 'like', $prefix.'%');
                }
            })
            ->count();
    }

    public function applyTierFilter(Builder $query, PlanTier $tier): Builder
    {
        if ($tier === PlanTier::Starter) {
            return $query->whereDoesntHave('subscriptions', fn (Builder $q) => $this->scopeValidSubscriptions($q)
                ->whereIn('stripe_price', $this->paidPriceIds() ?: ['__none__']));
        }

        return $query->whereHas('subscriptions', fn (Builder $q) => $this->scopeValidSubscriptions($q)
            ->whereIn('stripe_price', $this->priceIdsForSlug($tier->value) ?: ['__none__']));
    }

    /** Users who have used up the dynamic QR allowance of their tier. */
    public function applyAtDynamicLimitFilter(Builder $query): Builder
    {
        return $query->where(function (Builder $outer) {
            foreach (PlanTier::cases() as $tier) {
                $limit = $tier->maxDynamicQrCodes();

                if ($limit === null) {
                    continue; // unlimited tiers can never hit a cap
                }

                $outer->orWhere(function (Builder $q) use ($tier, $limit) {
                    $this->applyTierFilter($q, $tier);
                    $q->whereHas('qrCodes', fn (Builder $sub) => $sub->where('is_dynamic', true), '>=', $limit);
                });
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Overview
    |--------------------------------------------------------------------------
    */

    public function overviewStats(): array
    {
        $totalUsers = User::count();
        $mrr = $this->mrrCents();

        $paidUsers = User::whereHas('subscriptions', fn (Builder $q) => $this->scopeValidSubscriptions($q)
            ->whereIn('stripe_price', $this->paidPriceIds() ?: ['__none__']))->count();

        return [
            'total_users' => $totalUsers,
            'new_users_today' => User::where('created_at', '>=', now()->startOfDay())->count(),
            'new_users_30d' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'paid_users' => $paidUsers,
            'trialing_users' => Subscription::query()->where('type', 'default')->onTrial()->distinct()->count('user_id'),
            'comped_subscriptions' => $this->compedSubscriptionCount(),
            'mrr_cents' => $mrr,
            'arr_cents' => $mrr * 12,
            'arpu_cents' => $totalUsers > 0 ? (int) round($mrr / $totalUsers) : 0,
            'conversion_rate' => $totalUsers > 0 ? round(($paidUsers / $totalUsers) * 100, 1) : 0.0,
            'total_qr_codes' => QrCode::count(),
            'qr_codes_today' => QrCode::where('created_at', '>=', now()->startOfDay())->count(),
            'total_scans' => Scan::count(),
            'scans_today' => Scan::where('scanned_at', '>=', now()->startOfDay())->count(),
            'scans_30d' => Scan::where('scanned_at', '>=', now()->subDays(30))->count(),
            'one_off_30d_cents' => (int) PaidAction::where('status', 'completed')
                ->where('paid_at', '>=', now()->subDays(30))
                ->sum('amount_cents'),
        ];
    }

    public function mrrCents(): int
    {
        return $this->scopeRecurringPaid(Subscription::query())
            ->get()
            ->sum(fn (Subscription $s) => $this->monthlyCents($s) * max(1, (int) ($s->quantity ?? 1)));
    }

    /** @return array<int, array{tier: PlanTier, users: int, mrr_cents: int}> */
    public function mrrByTier(): array
    {
        $subscriptions = $this->scopeRecurringPaid(Subscription::query())->get();
        $rows = [];

        foreach (PlanTier::cases() as $tier) {
            if ($tier === PlanTier::Starter) {
                continue;
            }

            $forTier = $subscriptions->filter(fn (Subscription $s) => $this->tierForPriceId($s->stripe_price) === $tier);

            $rows[] = [
                'tier' => $tier,
                'users' => $forTier->count(),
                'mrr_cents' => (int) $forTier->sum(fn (Subscription $s) => $this->monthlyCents($s) * max(1, (int) ($s->quantity ?? 1))),
            ];
        }

        return $rows;
    }

    /** @return array<int, array{tier: PlanTier, users: int, share: float}> */
    public function planDistribution(): array
    {
        $total = max(1, User::count());
        $rows = [];

        foreach (PlanTier::cases() as $tier) {
            $count = $this->applyTierFilter(User::query(), $tier)->count();

            $rows[] = [
                'tier' => $tier,
                'users' => $count,
                'share' => round(($count / $total) * 100, 1),
            ];
        }

        return $rows;
    }

    public function attentionItems(): array
    {
        return [
            'at_limit' => $this->applyAtDynamicLimitFilter(User::query())->count(),
            'unverified' => User::whereNull('email_verified_at')->count(),
            'pending_paid_actions' => PaidAction::where('status', 'pending')->count(),
            'scheduled_deletions' => User::whereNotNull('account_deletion_scheduled_at')->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Time series
    |--------------------------------------------------------------------------
    */

    /** @return Collection<int, array{date: string, label: string, value: int}> */
    public function dailySeries(string $table, string $column, int $days = 30, ?callable $constrain = null): Collection
    {
        $query = DB::table($table)
            ->selectRaw("DATE({$column}) as day, COUNT(*) as aggregate")
            ->where($column, '>=', now()->subDays($days - 1)->startOfDay())
            ->groupBy('day');

        if ($constrain) {
            $constrain($query);
        }

        $rows = $query->pluck('aggregate', 'day');

        return $this->fillDays($rows, $days);
    }

    public function dailySignups(int $days = 30): Collection
    {
        return $this->dailySeries('users', 'created_at', $days);
    }

    public function dailyScans(int $days = 30): Collection
    {
        return $this->dailySeries('scans', 'scanned_at', $days);
    }

    public function dailyQrCodes(int $days = 30): Collection
    {
        return $this->dailySeries('qr_codes', 'created_at', $days, fn ($q) => $q->whereNull('deleted_at'));
    }

    /** @return Collection<int, array{date: string, label: string, value: int}> */
    protected function fillDays(Collection $rows, int $days): Collection
    {
        $series = collect();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');

            $series->push([
                'date' => $key,
                'label' => $date->format('M j'),
                'value' => (int) ($rows[$key] ?? 0),
            ]);
        }

        return $series;
    }

    /**
     * Monthly buckets built in PHP so the same code runs on MySQL and SQLite.
     *
     * @return Collection<int, array{month: string, label: string, value: int}>
     */
    public function monthlySeries(string $table, string $column, int $months = 12, string $aggregate = 'count', ?string $sumColumn = null, ?callable $constrain = null): Collection
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $select = $aggregate === 'sum'
            ? "DATE({$column}) as day, SUM({$sumColumn}) as aggregate"
            : "DATE({$column}) as day, COUNT(*) as aggregate";

        $query = DB::table($table)
            ->selectRaw($select)
            ->where($column, '>=', $start)
            ->groupBy('day');

        if ($constrain) {
            $constrain($query);
        }

        $byDay = $query->pluck('aggregate', 'day');
        $buckets = [];

        foreach ($byDay as $day => $value) {
            $key = Carbon::parse($day)->format('Y-m');
            $buckets[$key] = ($buckets[$key] ?? 0) + (int) $value;
        }

        $series = collect();

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth();
            $key = $date->format('Y-m');

            $series->push([
                'month' => $key,
                'label' => $date->format('M y'),
                'value' => (int) ($buckets[$key] ?? 0),
            ]);
        }

        return $series;
    }

    public function monthlyNewSubscriptions(int $months = 12): Collection
    {
        return $this->monthlySeries('subscriptions', 'created_at', $months, 'count', null, fn ($q) => $q->where('type', 'default'));
    }

    public function monthlyOneOffRevenue(int $months = 12): Collection
    {
        return $this->monthlySeries('paid_actions', 'paid_at', $months, 'sum', 'amount_cents', fn ($q) => $q->where('status', 'completed'));
    }

    /*
    |--------------------------------------------------------------------------
    | Revenue
    |--------------------------------------------------------------------------
    */

    public function revenueStats(): array
    {
        $mrr = $this->mrrCents();

        return [
            'mrr_cents' => $mrr,
            'arr_cents' => $mrr * 12,
            'active_subscriptions' => $this->scopeValidSubscriptions(Subscription::query())->count(),
            'trialing' => Subscription::query()->where('type', 'default')->onTrial()->count(),
            'comped_subscriptions' => $this->compedSubscriptionCount(),
            'canceled_30d' => Subscription::query()->where('type', 'default')
                ->whereNotNull('ends_at')
                ->where('updated_at', '>=', now()->subDays(30))
                ->count(),
            'one_off_total_cents' => (int) PaidAction::where('status', 'completed')->sum('amount_cents'),
            'one_off_30d_cents' => (int) PaidAction::where('status', 'completed')
                ->where('paid_at', '>=', now()->subDays(30))
                ->sum('amount_cents'),
            'one_off_pending' => PaidAction::where('status', 'pending')->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | System usage
    |--------------------------------------------------------------------------
    */

    public function usageStats(): array
    {
        $totalScans = Scan::count();
        $dynamicQr = QrCode::where('is_dynamic', true)->count();

        return [
            'total_qr_codes' => QrCode::count(),
            'dynamic_qr_codes' => $dynamicQr,
            'static_qr_codes' => QrCode::where('is_dynamic', false)->count(),
            'trashed_qr_codes' => QrCode::onlyTrashed()->count(),
            'qr_with_logo' => DB::table('qr_designs')->whereNotNull('logo_path')->where('logo_path', '!=', '')->count(),
            'total_scans' => $totalScans,
            'unique_scans' => Scan::where('is_unique', true)->count(),
            'unique_rate' => $totalScans > 0 ? round((Scan::where('is_unique', true)->count() / $totalScans) * 100, 1) : 0.0,
            'scans_today' => Scan::where('scanned_at', '>=', now()->startOfDay())->count(),
            'scans_7d' => Scan::where('scanned_at', '>=', now()->subDays(7))->count(),
            'scans_30d' => Scan::where('scanned_at', '>=', now()->subDays(30))->count(),
            'scans_per_dynamic_qr' => $dynamicQr > 0 ? round($totalScans / $dynamicQr, 1) : 0.0,
            'short_links' => ShortLink::count(),
            'links_active' => ShortLink::where('is_active', true)->count(),
            'links_expired' => ShortLink::whereNotNull('expires_at')->where('expires_at', '<', now())->count(),
            'links_password' => ShortLink::whereNotNull('password_hash')->count(),
            'links_capped' => ShortLink::whereNotNull('max_scans')->count(),
            'teams' => Team::count(),
            'team_members' => DB::table('team_user')->count(),
            'team_qr_codes' => QrCode::whereNotNull('team_id')->count(),
            'custom_domains' => CustomDomain::count(),
            'categories' => Category::count(),
            'api_tokens' => DB::table('personal_access_tokens')->count(),
        ];
    }

    /** @return Collection<int, object{label: string, count: int}> */
    public function qrCodesByType(): Collection
    {
        return QrCode::select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->orderByDesc('count')
            ->get();
    }

    /** @return Collection<int, object> */
    public function qrCodesByStatus(): Collection
    {
        return QrCode::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();
    }

    /** Global scan breakdown by country / device_type / browser / os. */
    public function scanBreakdown(string $column, int $days = 30, int $limit = 8): Collection
    {
        return Scan::select($column, DB::raw('COUNT(*) as count'))
            ->where('scanned_at', '>=', now()->subDays($days))
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column)
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, QrCode> */
    public function topQrCodes(int $limit = 10): Collection
    {
        return QrCode::with('user:id,name,email')
            ->where('is_dynamic', true)
            ->orderByDesc('total_scans')
            ->limit($limit)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Per-user
    |--------------------------------------------------------------------------
    */

    /**
     * Usage against the allowance of the user's current tier.
     *
     * @return array{tier: PlanTier, dynamic_used: int, dynamic_limit: ?int, dynamic_pct: ?float,
     *               static_used: int, static_limit: ?int, static_pct: ?float, at_limit: bool}
     */
    public function userLimits(User $user): array
    {
        $tier = $user->relationLoaded('subscriptions') ? $this->tierForLoadedUser($user) : $user->planTier();

        $dynamicUsed = $user->qr_dynamic_count ?? $user->qrCodes()->where('is_dynamic', true)->count();
        $staticUsed = $user->qr_static_count ?? $user->qrCodes()->where('is_dynamic', false)->count();

        $dynamicLimit = $tier->maxDynamicQrCodes();
        $staticLimit = $tier->maxStaticQrCodes();

        return [
            'tier' => $tier,
            'dynamic_used' => (int) $dynamicUsed,
            'dynamic_limit' => $dynamicLimit,
            'dynamic_pct' => $dynamicLimit ? min(100, round(($dynamicUsed / max(1, $dynamicLimit)) * 100, 1)) : null,
            'static_used' => (int) $staticUsed,
            'static_limit' => $staticLimit,
            'static_pct' => $staticLimit ? min(100, round(($staticUsed / max(1, $staticLimit)) * 100, 1)) : null,
            'at_limit' => $dynamicLimit !== null && $dynamicUsed >= $dynamicLimit,
        ];
    }

    public function userScanTotals(User $user): array
    {
        $shortLinkIds = $this->userShortLinkIds($user);

        return [
            'total' => Scan::whereIn('short_link_id', $shortLinkIds)->count(),
            'unique' => Scan::whereIn('short_link_id', $shortLinkIds)->where('is_unique', true)->count(),
            'last_30d' => Scan::whereIn('short_link_id', $shortLinkIds)->where('scanned_at', '>=', now()->subDays(30))->count(),
        ];
    }

    public function userDailyScans(User $user, int $days = 30): Collection
    {
        $shortLinkIds = $this->userShortLinkIds($user);

        if ($shortLinkIds->isEmpty()) {
            return $this->fillDays(collect(), $days);
        }

        return $this->dailySeries('scans', 'scanned_at', $days, fn ($q) => $q->whereIn('short_link_id', $shortLinkIds));
    }

    /**
     * Money actually collected from this user: completed one-off charges plus
     * the monthly price of the current subscription for every month it has run.
     */
    public function userLifetimeValueCents(User $user): int
    {
        $oneOff = (int) PaidAction::where('user_id', $user->id)->where('status', 'completed')->sum('amount_cents');

        $subscription = $this->validSubscription($user);

        if (! $subscription || $this->isComped($subscription)) {
            return $oneOff;
        }

        $monthsElapsed = max(1, $subscription->created_at->diffInMonths(now()) + 1);

        return $oneOff + ($this->monthlyCents($subscription) * $monthsElapsed);
    }

    protected function userShortLinkIds(User $user): Collection
    {
        return ShortLink::whereIn('qr_code_id', QrCode::where('user_id', $user->id)->select('id'))->pluck('id');
    }
}
