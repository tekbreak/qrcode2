<?php

namespace Tests\Feature\Admin;

use App\Enums\PlanTier;
use App\Livewire\Admin\AdminUserDetail;
use App\Livewire\Admin\AdminUsers;
use App\Models\QrCode;
use App\Models\User;
use App\Services\ImpersonationService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_grant_and_revoke_admin_access(): void
    {
        $target = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(AdminUsers::class)
            ->call('toggleAdmin', $target->id);

        $this->assertTrue($target->fresh()->is_admin);

        Livewire::actingAs($this->admin)
            ->test(AdminUsers::class)
            ->call('toggleAdmin', $target->id);

        $this->assertFalse($target->fresh()->is_admin);
    }

    public function test_an_admin_cannot_act_on_their_own_account(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminUsers::class)
            ->call('toggleAdmin', $this->admin->id);

        $this->assertTrue($this->admin->fresh()->is_admin, 'The admin flag must not be toggled on the acting admin.');

        Livewire::actingAs($this->admin)
            ->test(AdminUsers::class)
            ->call('deleteUser', $this->admin->id);

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_deleting_a_user_removes_their_qr_codes(): void
    {
        $target = User::factory()->create();
        $qrCode = QrCode::factory()->create(['user_id' => $target->id]);

        Livewire::actingAs($this->admin)
            ->test(AdminUsers::class)
            ->call('deleteUser', $target->id);

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('qr_codes', ['id' => $qrCode->id]);
    }

    public function test_admin_can_comp_a_paid_plan(): void
    {
        $target = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(AdminUserDetail::class, ['user' => $target])
            ->set('newPlan', 'enterprise')
            ->call('applyPlanChange');

        $this->assertSame(PlanTier::Enterprise, $target->fresh()->planTier());
        $this->assertStringStartsWith('admin_', $target->fresh()->subscriptions()->first()->stripe_id);
    }

    public function test_admin_can_cancel_a_subscription(): void
    {
        $target = User::factory()->create();
        app(SubscriptionService::class)->adminChangePlan($target, 'pro');

        $this->assertTrue($target->fresh()->subscribed('default'));

        Livewire::actingAs($this->admin)
            ->test(AdminUserDetail::class, ['user' => $target->fresh()])
            ->call('cancelUserSubscription');

        $this->assertFalse($target->fresh()->subscribed('default'));
        $this->assertSame(PlanTier::Starter, $target->fresh()->planTier());
    }

    public function test_downgrading_to_starter_ends_the_subscription(): void
    {
        $target = User::factory()->create();
        app(SubscriptionService::class)->adminChangePlan($target, 'pro');

        app(SubscriptionService::class)->adminChangePlan($target->fresh(), 'starter');

        $this->assertSame(PlanTier::Starter, $target->fresh()->planTier());
    }

    public function test_admin_can_impersonate_a_user(): void
    {
        $target = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(AdminUsers::class)
            ->call('impersonate', $target->id)
            ->assertRedirect(route('dashboard'));

        $this->assertSame($target->id, auth()->id());
        $this->assertSame($this->admin->id, session(ImpersonationService::SESSION_KEY));
    }

    public function test_the_impersonation_banner_offers_a_way_back(): void
    {
        $target = User::factory()->create();

        $this->actingAs($target);
        session([ImpersonationService::SESSION_KEY => $this->admin->id]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('admin.stop_impersonating'))
            ->assertSee(__('admin.impersonating_banner', ['name' => $target->name]));
    }

    public function test_impersonation_can_be_reversed(): void
    {
        $target = User::factory()->create();

        $this->actingAs($target);
        session([ImpersonationService::SESSION_KEY => $this->admin->id]);

        $this->post(route('impersonate.stop'))->assertRedirect(route('admin.users'));

        $this->assertSame($this->admin->id, auth()->id());
        $this->assertFalse(session()->has(ImpersonationService::SESSION_KEY));
    }

    public function test_a_non_admin_cannot_start_an_impersonation(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(ImpersonationService::class)->start(User::factory()->create(), User::factory()->create());
    }

    public function test_an_admin_cannot_impersonate_themselves(): void
    {
        $this->expectException(\RuntimeException::class);

        app(ImpersonationService::class)->start($this->admin, $this->admin);
    }

    public function test_stopping_without_an_impersonation_does_nothing(): void
    {
        $this->actingAs($this->admin);

        $this->assertNull(app(ImpersonationService::class)->stop());
    }
}
