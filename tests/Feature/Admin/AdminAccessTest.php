<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PlanSeeder::class);
    }

    public static function adminRoutes(): array
    {
        return [
            'overview' => ['admin.dashboard'],
            'users' => ['admin.users'],
            'revenue' => ['admin.revenue'],
            'usage' => ['admin.usage'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_guests_are_redirected_to_login(string $route): void
    {
        $this->get(route($route))->assertRedirect(route('login'));
    }

    #[DataProvider('adminRoutes')]
    public function test_regular_users_are_forbidden(string $route): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route($route))
            ->assertForbidden();
    }

    #[DataProvider('adminRoutes')]
    public function test_admins_can_open_every_admin_page(string $route): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route($route))
            ->assertOk();
    }

    public function test_regular_users_cannot_open_a_user_detail_page(): void
    {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.users.show', $target))
            ->assertForbidden();
    }

    public function test_admin_can_open_a_user_detail_page(): void
    {
        $target = User::factory()->create();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertSee($target->email);
    }
}
