<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrantAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_grants_admin_access(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com', 'is_admin' => false]);

        $this->artisan('user:admin', ['email' => 'jane@example.com'])
            ->assertExitCode(0);

        $this->assertTrue($user->fresh()->is_admin);
    }

    public function test_it_revokes_admin_access(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com', 'is_admin' => true]);

        $this->artisan('user:admin', ['email' => 'jane@example.com', '--revoke' => true])
            ->assertExitCode(0);

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_it_fails_on_an_unknown_email(): void
    {
        $this->artisan('user:admin', ['email' => 'nobody@example.com'])
            ->assertExitCode(1);
    }

    public function test_an_admin_reaches_the_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
            'selected_plan' => 'starter',
            'plan_selected_at' => now(),
        ]);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_an_unverified_admin_still_reaches_the_admin_dashboard(): void
    {
        $user = User::factory()->unverified()->create([
            'is_admin' => true,
            'selected_plan' => 'starter',
            'plan_selected_at' => now(),
        ]);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_an_admin_without_a_plan_is_sent_to_the_plan_selector(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
            'selected_plan' => null,
            'plan_selected_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('auth.choose-plan'));
    }

    public function test_a_non_admin_is_forbidden_from_the_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'selected_plan' => 'starter',
            'plan_selected_at' => now(),
        ]);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }
}
