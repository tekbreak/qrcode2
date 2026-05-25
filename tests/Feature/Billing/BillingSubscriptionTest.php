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

    public function test_dev_mode_subscribe_applies_starter_plan_and_credits(): void
    {
        $user = User::factory()->create();
        $user->createCreditBalance(PlanTier::Free);

        $result = app(SubscriptionService::class)->subscribe($user, 'starter', false);

        $this->assertSame('dev_applied', $result);
        $this->assertTrue($user->fresh()->subscribed('default'));
        $this->assertSame(PlanTier::Starter, $user->fresh()->planTier());
        $this->assertSame(50, $user->fresh()->creditBalance->balance);
        $this->assertSame(50, $user->fresh()->creditBalance->monthly_allowance);
    }

    public function test_dev_mode_downgrade_returns_user_to_free_plan(): void
    {
        $user = User::factory()->create();
        $user->createCreditBalance(PlanTier::Starter);

        app(SubscriptionService::class)->subscribe($user, 'starter', false);
        app(SubscriptionService::class)->subscribe($user, 'free', false);

        $this->assertFalse($user->fresh()->subscribed('default'));
        $this->assertSame(PlanTier::Free, $user->fresh()->planTier());
        $this->assertSame(5, $user->fresh()->creditBalance->balance);
    }

    public function test_livewire_subscribe_shows_success_message_in_dev_mode(): void
    {
        $user = User::factory()->create();
        $user->createCreditBalance(PlanTier::Free);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Billing\BillingIndex::class)
            ->call('subscribe', 'pro')
            ->assertSet('successMessage', 'Plan updated successfully (dev mode — no charge).');

        $this->assertSame(PlanTier::Pro, $user->fresh()->planTier());
        $this->assertSame(200, $user->fresh()->creditBalance->balance);
    }

    public function test_livewire_subscribe_shows_error_when_already_on_plan(): void
    {
        $user = User::factory()->create();
        $user->createCreditBalance(PlanTier::Free);

        app(SubscriptionService::class)->subscribe($user, 'starter', false);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Billing\BillingIndex::class)
            ->call('subscribe', 'starter')
            ->assertSet('errorMessage', 'You are already on this plan.');
    }

    public function test_plan_seeder_includes_dev_stripe_price_ids(): void
    {
        $starter = Plan::where('slug', 'starter')->first();

        $this->assertSame('dev_starter_monthly', $starter->stripe_monthly_price_id);
        $this->assertSame('dev_starter_yearly', $starter->stripe_yearly_price_id);
    }
}
