<?php

namespace Tests\Feature\Admin;

use App\Models\PaidAction;
use App\Models\QrCode;
use App\Models\Scan;
use App\Models\ShortLink;
use App\Models\Team;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders every admin page against a populated database, so branches that only
 * appear once there are rows (charts, badges, subscription tables) are covered.
 */
class AdminPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PlanSeeder::class);
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->seedActivity();
    }

    protected function seedActivity(): void
    {
        $pro = User::factory()->create(['name' => 'Paid Patty']);
        app(SubscriptionService::class)->subscribe($pro, 'pro', false);

        $enterprise = User::factory()->create(['name' => 'Big Betty']);
        app(SubscriptionService::class)->subscribe($enterprise, 'enterprise', true);

        $starter = User::factory()->unverified()->create([
            'name' => 'Starter Sam',
            'account_deletion_scheduled_at' => now()->subDay(),
        ]);

        $team = Team::create(['name' => 'Acme', 'owner_id' => $pro->id]);
        $team->users()->attach($starter->id, ['role' => 'member']);

        foreach ([$pro, $enterprise, $starter] as $user) {
            $qrCode = QrCode::factory()->dynamic()->create([
                'user_id' => $user->id,
                'total_scans' => 12,
            ]);
            QrCode::factory()->text()->create(['user_id' => $user->id]);

            $link = ShortLink::factory()->create(['qr_code_id' => $qrCode->id]);

            foreach (range(0, 4) as $day) {
                Scan::create([
                    'short_link_id' => $link->id,
                    'country' => 'ES',
                    'city' => 'Madrid',
                    'device_type' => 'mobile',
                    'os' => 'iOS',
                    'browser' => 'Safari',
                    'is_unique' => $day === 0,
                    'scanned_at' => now()->subDays($day),
                ]);
            }

            PaidAction::create([
                'user_id' => $user->id,
                'qr_code_id' => $qrCode->id,
                'action_type' => 'dynamic_edit',
                'status' => 'completed',
                'pending_data' => [],
                'amount_cents' => 100,
                'paid_at' => now()->subDays(2),
            ]);
        }

        PaidAction::create([
            'user_id' => $starter->id,
            'action_type' => 'dynamic_edit',
            'status' => 'pending',
            'pending_data' => [],
            'amount_cents' => 100,
        ]);
    }

    public function test_overview_renders_with_data(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Paid Patty')
            ->assertSee(__('admin.plan_distribution'))
            ->assertSee(__('admin.needs_attention'));
    }

    public function test_users_page_renders_with_data(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Paid Patty')
            ->assertSee('Big Betty')
            ->assertSee(__('admin.column_dynamic_usage'));
    }

    public function test_user_detail_renders_with_data(): void
    {
        $user = User::where('name', 'Paid Patty')->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee(__('admin.plan_and_limits'))
            ->assertSee(__('admin.payments'))
            ->assertSee('Acme');
    }

    public function test_revenue_page_renders_with_data(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.revenue'))
            ->assertOk()
            ->assertSee(__('admin.mrr_by_plan'))
            ->assertSee(__('admin.subscription_list'))
            ->assertSee('Paid Patty');
    }

    public function test_usage_page_renders_with_data(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.usage'))
            ->assertOk()
            ->assertSee(__('admin.scan_breakdown'))
            ->assertSee('Safari')
            ->assertSee(__('admin.top_qr_global'));
    }

    public function test_admin_pages_render_in_spanish(): void
    {
        $this->admin->update(['locale' => 'es']);
        app()->setLocale('es');

        $this->actingAs($this->admin)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee(__('admin.column_dynamic_usage', [], 'es'));
    }
}
