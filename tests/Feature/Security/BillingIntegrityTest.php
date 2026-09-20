<?php

namespace Tests\Feature\Security;

use App\Models\PaidAction;
use App\Models\Plan;
use App\Models\QrCode;
use App\Models\ShortLink;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_success_url_does_not_apply_an_unpaid_action(): void
    {
        $user = User::factory()->create();

        $qr = QrCode::create([
            'user_id' => $user->id, 'name' => 'q', 'type' => 'url',
            'is_dynamic' => true, 'content_data' => ['url' => 'https://old.example.com'],
        ]);
        $link = ShortLink::create([
            'qr_code_id' => $qr->id, 'slug' => ShortLink::generateSlug(),
            'destination_url' => 'https://old.example.com', 'is_active' => true,
        ]);

        $paidAction = PaidAction::create([
            'user_id' => $user->id,
            'qr_code_id' => $qr->id,
            'action_type' => 'edit_dynamic_qr',
            'status' => 'pending',
            'pending_data' => [
                'name' => 'q', 'type' => 'url',
                'content_data' => ['url' => 'https://attacker.example.com'],
                'destination_url' => 'https://attacker.example.com',
                'link_type' => 'redirect', 'is_active' => true,
            ],
            'amount_cents' => 100,
        ]);

        $this->actingAs($user)
            ->get(route('paid-actions.success', $paidAction))
            ->assertRedirect(route('qr-codes.index'));

        $this->assertSame('pending', $paidAction->fresh()->status);
        $this->assertNull($paidAction->fresh()->paid_at);
        $this->assertSame('https://old.example.com', $link->fresh()->destination_url);
    }

    public function test_a_forged_session_id_does_not_unlock_a_paid_action(): void
    {
        $user = User::factory()->create();
        $qr = QrCode::create([
            'user_id' => $user->id, 'name' => 'q', 'type' => 'url',
            'is_dynamic' => true, 'content_data' => ['url' => 'https://old.example.com'],
        ]);

        $paidAction = PaidAction::create([
            'user_id' => $user->id, 'qr_code_id' => $qr->id,
            'action_type' => 'edit_dynamic_qr', 'status' => 'pending',
            'stripe_checkout_session_id' => 'cs_test_real',
            'pending_data' => ['content_data' => ['url' => 'https://attacker.example.com']],
            'amount_cents' => 100,
        ]);

        $this->actingAs($user)
            ->get(route('paid-actions.success', $paidAction).'?session_id=cs_test_forged')
            ->assertRedirect(route('qr-codes.index'));

        $this->assertSame('pending', $paidAction->fresh()->status);
    }

    public function test_a_paid_action_cannot_be_started_without_stripe_outside_local(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['qrcode.paid_action_stripe_price_id' => null]);

        $user = User::factory()->create();
        $qr = QrCode::create([
            'user_id' => $user->id, 'name' => 'q', 'type' => 'url',
            'is_dynamic' => true, 'content_data' => ['url' => 'https://old.example.com'],
        ]);

        $this->expectException(\RuntimeException::class);

        app(\App\Services\PaidActionService::class)->createCheckout(
            $user, $qr, \App\Enums\PaidActionType::EditDynamicQr, ['destination_url' => 'https://x.example.com']
        );
    }

    public function test_plans_are_never_granted_locally_outside_local_or_testing(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['cashier.secret' => '', 'cashier.key' => '']);

        Plan::updateOrCreate(['slug' => 'enterprise'], [
            'name' => 'Enterprise', 'price_monthly' => 3900, 'price_yearly' => 38900,
            'stripe_monthly_price_id' => 'price_live_enterprise',
            'stripe_yearly_price_id' => 'price_live_enterprise_yearly',
            'is_active' => true, 'sort_order' => 3,
        ]);

        $user = User::factory()->create();

        $this->expectException(\RuntimeException::class);

        try {
            app(SubscriptionService::class)->subscribe($user, 'enterprise');
        } finally {
            $this->assertFalse($user->fresh()->subscribed());
        }
    }

    public function test_missing_billing_config_is_reported_but_never_aborts_the_boot(): void
    {
        // This application also serves every customer's QR redirect, so a billing
        // variable must never be able to take the site down.
        config([
            'cashier.secret' => '',
            'cashier.key' => '',
            'cashier.webhook.secret' => '',
            'qrcode.paid_action_stripe_price_id' => '',
        ]);

        $problems = \App\Providers\AppServiceProvider::billingConfigProblems();

        $this->assertCount(4, $problems);

        $this->artisan('billing:check')->assertExitCode(1);

        // The landing page and the short-link redirect both still work.
        $this->get('/')->assertOk();
    }

    public function test_the_stripe_webhook_rejects_unsigned_requests(): void
    {
        config(['cashier.webhook.secret' => null]);   // even with no secret configured

        $this->postJson('/stripe/webhook', [
            'id' => 'evt_test',
            'type' => 'customer.subscription.updated',
            'data' => ['object' => ['id' => 'sub_forged', 'customer' => 'cus_forged', 'status' => 'active']],
        ])->assertForbidden();
    }
}
