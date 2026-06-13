<?php

namespace Tests\Feature\Livewire;

use App\Enums\PlanTier;
use App\Livewire\Settings\SettingsIndex;
use App\Models\User;
use App\Services\SubscriptionService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
    }

    public function test_settings_page_renders_for_starter_users(): void
    {
        $user = User::factory()->create([
            'selected_plan' => PlanTier::Starter->value,
        ]);

        Livewire::actingAs($user)
            ->test(SettingsIndex::class)
            ->assertOk()
            ->assertSee(__('settings.profile'))
            ->assertDontSee(__('settings.team'));
    }

    public function test_settings_page_shows_team_section_for_enterprise_users(): void
    {
        $user = User::factory()->create();
        app(SubscriptionService::class)->subscribe($user, 'enterprise', false);

        Livewire::actingAs($user->fresh())
            ->test(SettingsIndex::class)
            ->assertOk()
            ->assertSee(__('settings.team'));
    }
}
