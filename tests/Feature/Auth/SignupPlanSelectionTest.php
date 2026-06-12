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
            ->assertRedirect(route('dashboard', ['welcome' => 1]));

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($user->plan_selected_at);
        $this->assertSame('starter', $user->selected_plan);
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->subscribed());
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
            ->assertRedirect(route('dashboard', ['welcome' => 1]));

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
        $user = User::factory()->create([
            'plan_selected_at' => null,
            'selected_plan' => null,
        ]);
        app(\App\Services\SubscriptionService::class)->subscribe($user, 'pro', false);

        $this->actingAs($user->fresh())
            ->get(route('auth.choose-plan'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_choose_plan_retries_after_user_was_created_on_failed_attempt(): void
    {
        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

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
            ->assertRedirect(route('dashboard', ['welcome' => 1]));

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertTrue($user->subscribed('default'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_without_plan_can_access_plan_selector(): void
    {
        $user = User::factory()->create([
            'plan_selected_at' => null,
            'selected_plan' => null,
        ]);

        $this->actingAs($user)
            ->get(route('auth.choose-plan'))
            ->assertOk();
    }

    public function test_authenticated_user_without_plan_is_redirected_from_dashboard(): void
    {
        $user = User::factory()->create([
            'plan_selected_at' => null,
            'selected_plan' => null,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('auth.choose-plan'));
    }

    public function test_choose_plan_redirects_even_when_verification_mail_fails(): void
    {
        $this->partialMock(User::class, function ($mock): void {
            $mock->shouldReceive('sendEmailVerificationNotification')
                ->andThrow(new \RuntimeException('MailFlash error'));
        });

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
            ->assertRedirect(route('dashboard', ['welcome' => 1]));
    }
}
