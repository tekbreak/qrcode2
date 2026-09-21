<?php

namespace Tests\Feature\Admin;

use App\Enums\PlanTier;
use App\Models\PaidAction;
use App\Models\Plan;
use App\Models\QrCode;
use App\Models\Scan;
use App\Models\ShortLink;
use App\Models\User;
use App\Services\AdminMetricsService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdminMetricsService $metrics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->metrics = app(AdminMetricsService::class);
    }

    /** A comped subscription: granted by an admin or by local dev billing. */
    protected function subscribed(string $plan, bool $yearly = false): User
    {
        $user = User::factory()->create();
        app(SubscriptionService::class)->subscribe($user, $plan, $yearly);

        return $user->fresh();
    }

    /** A subscription that really bills through Stripe. */
    protected function paying(string $plan, bool $yearly = false, ?string $trialEndsAt = null): User
    {
        $user = User::factory()->create();
        $model = Plan::where('slug', $plan)->firstOrFail();

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.Str::random(14),
            'stripe_status' => $trialEndsAt ? 'trialing' : 'active',
            'stripe_price' => $yearly ? $model->stripe_yearly_price_id : $model->stripe_monthly_price_id,
            'quantity' => 1,
            'trial_ends_at' => $trialEndsAt,
        ]);

        return $user->fresh();
    }

    public function test_mrr_sums_monthly_prices_of_active_paid_subscriptions(): void
    {
        $this->paying('pro');
        $this->paying('enterprise');
        User::factory()->create(); // starter, contributes nothing

        // Pro 1000c + Enterprise 3900c
        $this->assertSame(4900, $this->metrics->mrrCents());
    }

    public function test_yearly_subscriptions_count_as_one_twelfth_of_the_yearly_price(): void
    {
        $this->paying('enterprise', yearly: true);

        // 38900c / 12, rounded
        $this->assertSame((int) round(38900 / 12), $this->metrics->mrrCents());
    }

    public function test_trialing_subscriptions_are_excluded_from_mrr(): void
    {
        $this->paying('pro', trialEndsAt: now()->addDays(14)->toDateTimeString());

        $this->assertSame(0, $this->metrics->mrrCents());
        $this->assertSame(1, $this->metrics->overviewStats()['trialing_users']);
    }

    public function test_canceled_subscriptions_are_excluded_from_mrr(): void
    {
        $user = $this->paying('pro');

        // What Stripe's webhook leaves behind once a cancellation takes effect.
        $user->subscriptions()->first()->update([
            'stripe_status' => 'canceled',
            'ends_at' => now()->subDay(),
        ]);

        $this->assertSame(0, $this->metrics->mrrCents());
    }

    public function test_comped_subscriptions_are_excluded_from_mrr_but_counted(): void
    {
        $this->subscribed('pro');          // comped, collects nothing
        $this->paying('pro');              // real money

        $this->assertSame(1000, $this->metrics->mrrCents());
        $this->assertSame(1, $this->metrics->compedSubscriptionCount());
        $this->assertSame(1, $this->metrics->revenueStats()['comped_subscriptions']);
    }

    public function test_an_admin_granted_plan_is_treated_as_comped(): void
    {
        $user = User::factory()->create();
        app(SubscriptionService::class)->adminChangePlan($user, 'enterprise');

        $this->assertSame(0, $this->metrics->mrrCents());
        $this->assertSame(1, $this->metrics->compedSubscriptionCount());
    }

    public function test_plan_distribution_counts_every_tier(): void
    {
        User::factory()->count(2)->create();
        $this->subscribed('pro');

        $distribution = collect($this->metrics->planDistribution())->keyBy(fn ($row) => $row['tier']->value);

        $this->assertSame(2, $distribution['starter']['users']);
        $this->assertSame(1, $distribution['pro']['users']);
        $this->assertSame(0, $distribution['enterprise']['users']);
    }

    public function test_user_limits_report_usage_against_the_tier_allowance(): void
    {
        $user = User::factory()->create();
        QrCode::factory()->count(3)->create(['user_id' => $user->id]);
        QrCode::factory()->dynamic()->create(['user_id' => $user->id]);

        $limits = $this->metrics->userLimits($user->fresh());

        $this->assertSame(PlanTier::Starter, $limits['tier']);
        $this->assertSame(3, $limits['static_used']);
        $this->assertSame(5, $limits['static_limit']);
        $this->assertSame(1, $limits['dynamic_used']);
        $this->assertSame(1, $limits['dynamic_limit']);
        $this->assertTrue($limits['at_limit']);
    }

    public function test_at_dynamic_limit_filter_matches_only_capped_users(): void
    {
        $capped = User::factory()->create();
        QrCode::factory()->dynamic()->create(['user_id' => $capped->id]); // starter cap is 1

        $roomy = User::factory()->create(); // no dynamic QR codes at all

        $pro = $this->subscribed('pro');
        QrCode::factory()->dynamic()->create(['user_id' => $pro->id]); // 1 of 10

        $ids = $this->metrics->applyAtDynamicLimitFilter(User::query())->pluck('id');

        $this->assertTrue($ids->contains($capped->id));
        $this->assertFalse($ids->contains($roomy->id));
        $this->assertFalse($ids->contains($pro->id));
    }

    public function test_lifetime_value_adds_completed_one_off_charges(): void
    {
        $user = User::factory()->create();

        PaidAction::create([
            'user_id' => $user->id,
            'action_type' => 'dynamic_edit',
            'status' => 'completed',
            'pending_data' => [],
            'amount_cents' => 100,
            'paid_at' => now(),
        ]);

        PaidAction::create([
            'user_id' => $user->id,
            'action_type' => 'dynamic_edit',
            'status' => 'pending',
            'pending_data' => [],
            'amount_cents' => 100,
        ]);

        $this->assertSame(100, $this->metrics->userLifetimeValueCents($user));
    }

    public function test_comped_subscriptions_do_not_inflate_lifetime_value(): void
    {
        $user = $this->subscribed('pro'); // dev_ subscription in the test environment

        $this->assertSame(0, $this->metrics->userLifetimeValueCents($user));
    }

    public function test_daily_scan_series_covers_the_whole_window(): void
    {
        $qrCode = QrCode::factory()->dynamic()->create();
        $link = ShortLink::factory()->create(['qr_code_id' => $qrCode->id]);

        Scan::create(['short_link_id' => $link->id, 'is_unique' => true, 'scanned_at' => now()]);
        Scan::create(['short_link_id' => $link->id, 'is_unique' => false, 'scanned_at' => now()->subDays(3)]);
        Scan::create(['short_link_id' => $link->id, 'is_unique' => false, 'scanned_at' => now()->subDays(60)]);

        $series = $this->metrics->dailyScans(30);

        $this->assertCount(30, $series);
        $this->assertSame(2, $series->sum('value'), 'Only scans inside the window are counted.');
        $this->assertSame(1, $series->last()['value'], 'Today is the last bucket.');
    }

    public function test_usage_stats_describe_the_platform(): void
    {
        $qrCode = QrCode::factory()->dynamic()->create();
        ShortLink::factory()->passwordProtected()->create(['qr_code_id' => $qrCode->id]);
        QrCode::factory()->create();

        $stats = $this->metrics->usageStats();

        $this->assertSame(2, $stats['total_qr_codes']);
        $this->assertSame(1, $stats['dynamic_qr_codes']);
        $this->assertSame(1, $stats['static_qr_codes']);
        $this->assertSame(1, $stats['links_password']);
    }
}
