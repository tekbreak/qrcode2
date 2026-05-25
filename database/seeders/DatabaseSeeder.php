<?php

namespace Database\Seeders;

use App\Enums\PlanTier;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PlanSeeder::class);

        $this->seedDemoUser([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        $this->seedDemoUser([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    private function seedDemoUser(array $attributes): User
    {
        $user = User::updateOrCreate(
            ['email' => $attributes['email']],
            array_merge([
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ], $attributes)
        );

        $allowance = PlanTier::Free->monthlyCredits();

        $user->creditBalance()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => $allowance,
                'monthly_allowance' => $allowance,
                'resets_at' => now()->addMonth()->startOfMonth(),
            ]
        );

        return $user;
    }
}
