<?php

namespace Tests\Feature\Api;

use App\Enums\CreditAction;
use App\Enums\PlanTier;
use App\Enums\QrCodeType;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QrCodeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_codes_index_requires_authentication(): void
    {
        $this->getJson('/api/qr-codes')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_list_own_qr_codes(): void
    {
        $user = User::factory()->create();
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
        $other = User::factory()->create();
        $qrCode = QrCode::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($other);

        $this->getJson("/api/qr-codes/{$qrCode->id}")
            ->assertForbidden();
    }

    public function test_store_creates_qr_code_and_deducts_api_credits(): void
    {
        $user = User::factory()->create();
        $user->createCreditBalance(PlanTier::Starter);
        Sanctum::actingAs($user);

        $startingBalance = $user->creditBalance->balance;

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

        $this->assertSame(
            $startingBalance - CreditAction::ApiCall->cost(),
            $user->fresh()->creditBalance->balance
        );
    }

    public function test_store_returns_403_when_plan_limit_reached(): void
    {
        $user = User::factory()->create();
        $user->createCreditBalance(PlanTier::Free);
        QrCode::factory()->count(3)->create([
            'user_id' => $user->id,
            'is_dynamic' => false,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/qr-codes', [
            'name' => 'Over limit',
            'type' => QrCodeType::Text->value,
            'is_dynamic' => false,
            'content_data' => ['text' => 'hello'],
        ])->assertForbidden()
            ->assertJsonPath('error', 'Plan QR code limit reached.');
    }

    public function test_destroy_deletes_own_qr_code(): void
    {
        $user = User::factory()->create();
        $qrCode = QrCode::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/qr-codes/{$qrCode->id}")
            ->assertOk();

        $this->assertSoftDeleted('qr_codes', ['id' => $qrCode->id]);
    }
}
