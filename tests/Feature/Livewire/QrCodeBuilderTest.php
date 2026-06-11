<?php

namespace Tests\Feature\Livewire;

use App\Enums\PlanTier;
use App\Livewire\QrCodes\QrCodeBuilder;
use App\Models\QrCode;
use App\Models\ShortLink;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QrCodeBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PlanSeeder::class);
    }

    public function test_builder_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QrCodeBuilder::class)
            ->assertOk()
            ->assertSet('step', 1);
    }

    public function test_next_step_advances_wizard(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QrCodeBuilder::class)
            ->set('name', 'Test QR')
            ->set('type', 'text')
            ->set('text', 'Hello world')
            ->call('nextStep')
            ->assertSet('step', 2);
    }

    public function test_save_creates_static_qr_code(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(QrCodeBuilder::class)
            ->set('name', 'Saved QR')
            ->set('type', 'text')
            ->set('text', 'Static content')
            ->call('save')
            ->assertRedirect(route('qr-codes.index'));

        $this->assertDatabaseHas('qr_codes', [
            'user_id' => $user->id,
            'name' => 'Saved QR',
            'is_dynamic' => false,
        ]);
    }

    public function test_save_blocks_creation_when_plan_limit_reached(): void
    {
        $user = User::factory()->create();
        QrCode::factory()->count(5)->create([
            'user_id' => $user->id,
            'is_dynamic' => false,
        ]);

        Livewire::actingAs($user)
            ->test(QrCodeBuilder::class)
            ->set('name', 'Sixth QR')
            ->set('type', 'text')
            ->set('text', 'Too many')
            ->call('save')
            ->assertHasErrors(['name']);

        $this->assertDatabaseMissing('qr_codes', [
            'user_id' => $user->id,
            'name' => 'Sixth QR',
        ]);
    }

    public function test_first_dynamic_activation_does_not_require_payment(): void
    {
        $user = User::factory()->create();

        $qrCode = QrCode::factory()->create([
            'user_id' => $user->id,
            'type' => 'url',
            'is_dynamic' => false,
            'content_data' => ['url' => 'https://example.com'],
        ]);

        Livewire::actingAs($user)
            ->test(QrCodeBuilder::class, ['qrCode' => $qrCode])
            ->set('url', 'https://updated.example.com')
            ->call('save')
            ->assertRedirect(route('qr-codes.index'));

        $this->assertTrue($qrCode->fresh()->is_dynamic);
        $this->assertDatabaseHas('short_links', [
            'qr_code_id' => $qrCode->id,
            'destination_url' => 'https://updated.example.com',
        ]);
    }

    public function test_enterprise_user_can_edit_dynamic_qr_without_payment(): void
    {
        $user = User::factory()->create();
        app(SubscriptionService::class)->subscribe($user, 'enterprise', false);

        $qrCode = QrCode::factory()->create([
            'user_id' => $user->id,
            'type' => 'url',
            'is_dynamic' => true,
            'content_data' => ['url' => 'https://example.com'],
        ]);

        ShortLink::create([
            'qr_code_id' => $qrCode->id,
            'slug' => ShortLink::generateSlug(),
            'destination_url' => 'https://example.com',
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(QrCodeBuilder::class, ['qrCode' => $qrCode])
            ->set('url', 'https://enterprise-updated.example.com')
            ->call('save')
            ->assertRedirect(route('qr-codes.index'));

        $this->assertSame(
            'https://enterprise-updated.example.com',
            $qrCode->fresh()->shortLink->destination_url
        );
    }
}
