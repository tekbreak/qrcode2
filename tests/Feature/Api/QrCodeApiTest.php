<?php

namespace Tests\Feature\Api;

use App\Enums\PlanTier;
use App\Enums\QrCodeType;
use App\Models\QrCode;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QrCodeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PlanSeeder::class);
    }

    public function test_qr_codes_index_requires_authentication(): void
    {
        $this->getJson('/api/qr-codes')
            ->assertUnauthorized();
    }

    public function test_api_access_requires_pro_plan(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/qr-codes')
            ->assertForbidden()
            ->assertJsonPath('error', 'API access is not available on your plan.');
    }

    public function test_authenticated_pro_user_can_list_own_qr_codes(): void
    {
        $user = User::factory()->create();
        app(SubscriptionService::class)->subscribe($user, 'pro', false);
        QrCode::factory()->count(2)->create(['user_id' => $user->id]);
        QrCode::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/qr-codes')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_user_cannot_view_another_users_qr_code(): void
    {
        $owner = User::factory()->create();
        app(SubscriptionService::class)->subscribe($owner, 'pro', false);
        $other = User::factory()->create();
        app(SubscriptionService::class)->subscribe($other, 'pro', false);
        $qrCode = QrCode::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($other);

        $this->getJson("/api/qr-codes/{$qrCode->id}")
            ->assertForbidden();
    }

    public function test_store_creates_qr_code_for_pro_user(): void
    {
        $user = User::factory()->create();
        app(SubscriptionService::class)->subscribe($user, 'pro', false);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/qr-codes', [
            'name' => 'API QR',
            'type' => QrCodeType::Url->value,
            'is_dynamic' => false,
            'content_data' => ['url' => 'https://example.com'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'API QR');

        $this->assertDatabaseHas('qr_codes', [
            'user_id' => $user->id,
            'name' => 'API QR',
        ]);
    }

    public function test_store_returns_403_when_dynamic_plan_limit_reached(): void
    {
        $user = User::factory()->create();
        app(SubscriptionService::class)->subscribe($user, 'pro', false);
        QrCode::factory()->count(10)->create([
            'user_id' => $user->id,
            'is_dynamic' => true,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/qr-codes', [
            'name' => 'Over limit',
            'type' => QrCodeType::Url->value,
            'is_dynamic' => true,
            'content_data' => ['url' => 'https://example.com'],
        ])->assertForbidden()
            ->assertJsonPath('error', 'Plan QR code limit reached.');
    }

    public function test_destroy_deletes_own_qr_code(): void
    {
        $user = User::factory()->create();
        app(SubscriptionService::class)->subscribe($user, 'pro', false);
        $qrCode = QrCode::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/qr-codes/{$qrCode->id}")
            ->assertOk();

        $this->assertSoftDeleted('qr_codes', ['id' => $qrCode->id]);
    }
}
