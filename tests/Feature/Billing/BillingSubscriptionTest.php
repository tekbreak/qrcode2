<?php

namespace Tests\Feature\Billing;

use App\Enums\PlanTier;
use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillingSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PlanSeeder::class);
    }

    public function test_dev_mode_subscribe_applies_pro_plan(): void
    {
        $user = User::factory()->create();

        $result = app(SubscriptionService::class)->subscribe($user, 'pro', false);

        $this->assertSame('dev_applied', $result);
        $this->assertTrue($user->fresh()->subscribed('default'));
        $this->assertSame(PlanTier::Pro, $user->fresh()->planTier());
    }

    public function test_dev_mode_downgrade_returns_user_to_starter_plan(): void
    {
        $user = User::factory()->create();

        app(SubscriptionService::class)->subscribe($user, 'pro', false);
        app(SubscriptionService::class)->subscribe($user, 'starter', false);

        $this->assertFalse($user->fresh()->subscribed('default'));
        $this->assertSame(PlanTier::Starter, $user->fresh()->planTier());
    }

    public function test_livewire_subscribe_shows_success_message_in_dev_mode(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Billing\BillingIndex::class)
            ->call('subscribe', 'pro')
            ->assertSet('successMessage', 'Plan updated successfully (dev mode — no charge).');

        $this->assertSame(PlanTier::Pro, $user->fresh()->planTier());
    }

    public function test_livewire_subscribe_shows_error_when_already_on_plan(): void
    {
        $user = User::factory()->create();

        app(SubscriptionService::class)->subscribe($user, 'pro', false);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Billing\BillingIndex::class)
            ->call('subscribe', 'pro')
            ->assertSet('successMessage', 'You are already on this plan.');
    }

    public function test_plan_seeder_includes_dev_stripe_price_ids(): void
    {
        $pro = Plan::where('slug', 'pro')->first();

        $this->assertSame('dev_pro_monthly', $pro->stripe_monthly_price_id);
        $this->assertSame('dev_pro_yearly', $pro->stripe_yearly_price_id);
    }

    public function test_publishable_key_in_secret_falls_back_to_dev_mode(): void
    {
        config([
            'cashier.key' => 'pk_test_example',
            'cashier.secret' => 'pk_test_example',
        ]);

        Plan::where('slug', 'pro')->update([
            'stripe_monthly_price_id' => 'price_live_example',
        ]);

        $user = User::factory()->create();

        $result = app(SubscriptionService::class)->subscribe($user, 'pro', false, withTrial: true);

        $this->assertSame('dev_applied', $result);
        $this->assertTrue($user->fresh()->subscribed('default'));
        $this->assertNotNull($user->fresh()->subscription()->trial_ends_at);
    }
}
