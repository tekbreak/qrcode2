<?php

namespace Database\Seeders;

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
        // PlanSeeder holds reference data every environment needs. The demo
        // accounts use a shared, published password and must never exist outside
        // development.
        $this->call(PlanSeeder::class);

        if (app()->environment('local', 'testing')) {
            $this->call(MockUserSeeder::class);
        }
    }
}
