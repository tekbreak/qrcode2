<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class MockUserSeeder extends Seeder
{
    public const PASSWORD = 'password';

    /**
     * @return array<int, array{name: string, email: string, role_label: string, is_admin?: bool}>
     */
    public static function accounts(): array
    {
        return [
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'is_admin' => true,
                'role_label' => 'Administrator',
            ],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'role_label' => 'Standard user',
            ],
            [
                'name' => 'Demo User',
                'email' => 'demo@example.com',
                'role_label' => 'Standard user',
            ],
        ];
    }

    public static function emails(): array
    {
        return array_column(self::accounts(), 'email');
    }

    public function run(): void
    {
        foreach (self::accounts() as $account) {
            $this->seedAccount($account);
        }
    }

    /**
     * @param  array{name: string, email: string, role_label: string, is_admin?: bool}  $account
     */
    private function seedAccount(array $account): User
    {
        return User::updateOrCreate(
            ['email' => $account['email']],
            [
                'name' => $account['name'],
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
                'is_admin' => $account['is_admin'] ?? false,
            ]
        );
    }
}
