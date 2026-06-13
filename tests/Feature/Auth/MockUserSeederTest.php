<?php

namespace Tests\Feature\Auth;

use App\Enums\PlanTier;
use App\Models\User;
use Database\Seeders\MockUserSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockUserSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
    }

    public function test_mock_users_have_plan_selection_and_expected_tiers(): void
    {
        $this->seed(MockUserSeeder::class);

        foreach (MockUserSeeder::accounts() as $account) {
            $user = User::where('email', $account['email'])->first();

            $this->assertNotNull($user);
            $this->assertSame($account['plan'], $user->selected_plan);
            $this->assertNotNull($user->plan_selected_at);
            $this->assertTrue($user->hasSelectedPlan());
            $this->assertSame(
                MockUserSeeder::expectedPlanTier($account['email']),
                $user->planTier(),
                "Unexpected plan tier for {$account['email']}",
            );
        }
    }

    public function test_reseeding_mock_users_backfills_missing_plan_selection(): void
    {
        foreach (MockUserSeeder::accounts() as $account) {
            User::factory()->create([
                'email' => $account['email'],
                'name' => $account['name'],
                'selected_plan' => null,
                'plan_selected_at' => null,
            ]);
        }

        $this->seed(MockUserSeeder::class);

        foreach (MockUserSeeder::accounts() as $account) {
            $user = User::where('email', $account['email'])->first();

            $this->assertTrue($user->hasSelectedPlan());
            $this->assertSame($account['plan'], $user->selected_plan);
        }
    }
}
