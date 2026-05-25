<?php

namespace Tests\Feature\Livewire;

use App\Enums\PlanTier;
use App\Livewire\QrCodes\QrCodeBuilder;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QrCodeBuilderTest extends TestCase
{
    use RefreshDatabase;

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
        $user->createCreditBalance(PlanTier::Free);

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
        $user->createCreditBalance(PlanTier::Free);
        QrCode::factory()->count(3)->create([
            'user_id' => $user->id,
            'is_dynamic' => false,
        ]);

        Livewire::actingAs($user)
            ->test(QrCodeBuilder::class)
            ->set('name', 'Fourth QR')
            ->set('type', 'text')
            ->set('text', 'Too many')
            ->call('save')
            ->assertHasErrors(['name']);

        $this->assertDatabaseMissing('qr_codes', [
            'user_id' => $user->id,
            'name' => 'Fourth QR',
        ]);
    }

    public function test_editing_dynamic_qr_requires_credit_confirmation(): void
    {
        $user = User::factory()->create();
        $user->createCreditBalance(PlanTier::Starter);

        $qrCode = QrCode::factory()->create([
            'user_id' => $user->id,
            'type' => 'url',
            'is_dynamic' => false,
            'content_data' => ['url' => 'https://example.com'],
        ]);

        Livewire::actingAs($user)
            ->test(QrCodeBuilder::class, ['qrCode' => $qrCode])
            ->set('url', 'https://updated.example.com')
            ->set('confirmCreditCharge', false)
            ->call('save')
            ->assertHasErrors(['confirmCreditCharge']);
    }
}
