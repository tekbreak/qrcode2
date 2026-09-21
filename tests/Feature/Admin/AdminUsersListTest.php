<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\AdminUsers;
use App\Models\QrCode;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUsersListTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Root Admin']);
    }

    public function test_search_matches_name_and_email(): void
    {
        User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
        User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);

        Livewire::actingAs($this->admin)
            ->test(AdminUsers::class)
            ->set('search', 'ada')
            ->assertSee('Ada Lovelace')
            ->assertDontSee('Grace Hopper');
    }

    public function test_plan_filter_narrows_the_list(): void
    {
        $pro = User::factory()->create(['name' => 'Paid Patty']);
        app(SubscriptionService::class)->subscribe($pro, 'pro', false);

        User::factory()->create(['name' => 'Free Freddie']);

        Livewire::actingAs($this->admin)
            ->test(AdminUsers::class)
            ->set('plan', 'pro')
            ->assertSee('Paid Patty')
            ->assertDontSee('Free Freddie');
    }

    public function test_at_limit_flag_shows_only_capped_users(): void
    {
        $capped = User::factory()->create(['name' => 'Capped Casey']);
        QrCode::factory()->dynamic()->create(['user_id' => $capped->id]);

        User::factory()->create(['name' => 'Roomy Robin']);

        Livewire::actingAs($this->admin)
            ->test(AdminUsers::class)
            ->set('flag', 'at_limit')
            ->assertSee('Capped Casey')
            ->assertDontSee('Roomy Robin');
    }

    public function test_unverified_flag_shows_only_unverified_users(): void
    {
        User::factory()->unverified()->create(['name' => 'Unverified Uma']);
        User::factory()->create(['name' => 'Verified Vera']);

        Livewire::actingAs($this->admin)
            ->test(AdminUsers::class)
            ->set('flag', 'unverified')
            ->assertSee('Unverified Uma')
            ->assertDontSee('Verified Vera');
    }

    public function test_sorting_toggles_direction_on_the_same_column(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminUsers::class)
            ->call('sortBy', 'name')
            ->assertSet('sort', 'name')
            ->assertSet('direction', 'asc')
            ->call('sortBy', 'name')
            ->assertSet('direction', 'desc');
    }

    public function test_unknown_sort_columns_are_ignored(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminUsers::class)
            ->call('sortBy', 'password')
            ->assertSet('sort', 'created_at');
    }

    public function test_reset_filters_clears_every_filter(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AdminUsers::class)
            ->set('search', 'ada')
            ->set('plan', 'pro')
            ->set('flag', 'unverified')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('plan', 'all')
            ->assertSet('flag', 'all');
    }

    public function test_csv_export_streams_the_filtered_users(): void
    {
        User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
        User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.com']);

        $response = Livewire::actingAs($this->admin)
            ->test(AdminUsers::class)
            ->set('search', 'ada')
            ->call('exportCsv');

        $csv = $this->captureDownload($response);

        $this->assertStringContainsString('ada@example.com', $csv);
        $this->assertStringNotContainsString('grace@example.com', $csv);
        $this->assertStringContainsString('dynamic_limit', $csv);
    }

    protected function captureDownload($response): string
    {
        $download = $response->effects['download'] ?? null;

        $this->assertNotNull($download, 'The component did not return a file download.');

        return base64_decode($download['content']);
    }
}
