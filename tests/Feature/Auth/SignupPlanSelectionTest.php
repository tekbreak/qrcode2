<?php

namespace Tests\Feature\Auth;

use App\Enums\PlanTier;
use App\Livewire\Auth\ChoosePlan;
use App\Livewire\Auth\Register;
use App\Models\User;
use App\Services\SignupService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SignupPlanSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
    }

    public function test_register_redirects_to_plan_selector_without_creating_user(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@example.com')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect(route('auth.choose-plan'));

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
        $this->assertTrue(app(SignupService::class)->hasPendingSignup());
    }

    public function test_choose_plan_creates_starter_account(): void
    {
        $this->withSession([
            SignupService::SESSION_KEY => [
                'type' => 'email',
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'password123',
            ],
        ]);

        Livewire::test(ChoosePlan::class)
            ->call('selectPlan', 'starter')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        $this->assertAuthenticatedAs(User::where('email', 'jane@example.com')->first());
        $this->assertFalse(User::where('email', 'jane@example.com')->first()->subscribed());
    }

    public function test_choose_plan_applies_trial_subscription_for_paid_plan_in_dev_mode(): void
    {
        $this->withSession([
            SignupService::SESSION_KEY => [
                'type' => 'email',
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'password123',
            ],
        ]);

        Livewire::test(ChoosePlan::class)
            ->call('selectPlan', 'pro')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertTrue($user->subscribed('default'));
        $this->assertSame(PlanTier::Pro, $user->planTier());
        $this->assertNotNull($user->subscription()->trial_ends_at);
        $this->assertTrue($user->subscription()->trial_ends_at->isFuture());
    }

    public function test_choose_plan_requires_pending_signup_for_guests(): void
    {
        $this->get(route('auth.choose-plan'))
            ->assertRedirect(route('register'));
    }

    public function test_subscribed_user_is_redirected_away_from_plan_selector(): void
    {
        $user = User::factory()->create();
        app(\App\Services\SubscriptionService::class)->subscribe($user, 'pro', false);

        $this->actingAs($user->fresh())
            ->get(route('auth.choose-plan'))
            ->assertRedirect(route('dashboard'));
    }
}
