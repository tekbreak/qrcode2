<?php

namespace Tests\Feature\Billing;

use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\SubscriptionSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PlanSeeder::class);
    }

    public function test_command_skips_when_stripe_is_not_configured_for_live_billing(): void
    {
        $this->artisan('subscriptions:sync')
            ->expectsOutputToContain('Stripe is not configured for live billing')
            ->assertSuccessful();
    }

    public function test_dev_subscriptions_are_skipped(): void
    {
        $user = User::factory()->create();

        app(SubscriptionService::class)->subscribe($user, 'pro', false);

        $syncService = app(SubscriptionSyncService::class);

        config([
            'cashier.key' => 'pk_test_example',
            'cashier.secret' => 'sk_test_example',
        ]);

        $subscription = $user->fresh()->subscription();
        $result = $syncService->syncSubscription($subscription);

        $this->assertSame('skipped', $result);
    }

    public function test_command_processes_users_without_live_subscriptions(): void
    {
        User::factory()->count(3)->create();

        $this->artisan('subscriptions:sync')
            ->expectsOutputToContain('Stripe is not configured for live billing')
            ->assertSuccessful();
    }
}
