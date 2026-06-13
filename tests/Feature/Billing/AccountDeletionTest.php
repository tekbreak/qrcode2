<?php

namespace Tests\Feature\Billing;

use App\Mail\AccountDeletionWarningMail;
use App\Models\QrCode;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PlanSeeder::class);
    }

    public function test_lapsed_live_subscription_schedules_deletion_and_sends_email(): void
    {
        Mail::fake();

        $user = $this->createLapsedLiveSubscriber();

        $result = app(AccountDeletionService::class)->processUser($user);

        $this->assertSame('scheduled', $result);
        $this->assertNotNull($user->fresh()->account_deletion_scheduled_at);

        Mail::assertQueued(AccountDeletionWarningMail::class, function (AccountDeletionWarningMail $mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_deletion_is_performed_after_grace_period(): void
    {
        Mail::fake();

        $user = $this->createLapsedLiveSubscriber([
            'account_deletion_scheduled_at' => now()->subDays(8),
        ]);

        QrCode::factory()->create(['user_id' => $user->id]);

        $result = app(AccountDeletionService::class)->processUser($user);

        $this->assertSame('deleted', $result);
        $this->assertNull(User::find($user->id));
        $this->assertDatabaseMissing('qr_codes', ['user_id' => $user->id]);
    }

    public function test_resubscribing_clears_scheduled_deletion(): void
    {
        $user = $this->createLapsedLiveSubscriber([
            'account_deletion_scheduled_at' => now()->subDay(),
        ]);

        app(SubscriptionService::class)->subscribe($user, 'pro', false);

        $this->assertNull($user->fresh()->account_deletion_scheduled_at);
        $this->assertTrue($user->fresh()->subscribed('default'));
    }

    public function test_starter_only_users_are_not_scheduled_for_deletion(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $result = app(AccountDeletionService::class)->processUser($user);

        $this->assertSame('skipped', $result);
        $this->assertNull($user->fresh()->account_deletion_scheduled_at);
        Mail::assertNothingSent();
    }

    public function test_admin_users_are_never_scheduled_for_deletion(): void
    {
        Mail::fake();

        $user = $this->createLapsedLiveSubscriber(['is_admin' => true]);

        $result = app(AccountDeletionService::class)->processUser($user);

        $this->assertSame('skipped', $result);
        Mail::assertNothingSent();
    }

    public function test_command_runs_deletion_workflow_when_stripe_is_not_configured(): void
    {
        Mail::fake();

        $this->createLapsedLiveSubscriber();

        $this->artisan('subscriptions:sync')
            ->expectsOutputToContain('Skipping Stripe reconciliation')
            ->expectsOutputToContain('Deletion scheduled: 1')
            ->assertSuccessful();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createLapsedLiveSubscriber(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);

        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_live_test_'.fake()->uuid(),
            'stripe_status' => 'canceled',
            'stripe_price' => 'price_live_test',
            'quantity' => 1,
            'ends_at' => now()->subDay(),
        ]);

        return $user->fresh(['subscriptions']);
    }
}
