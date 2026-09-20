<?php

namespace Tests\Feature\Security;

use App\Livewire\Analytics\AnalyticsIndex;
use App\Livewire\QrCodes\QrCodeBuilder;
use App\Livewire\Teams\TeamManager;
use App\Models\QrCode;
use App\Models\ShortLink;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    private function qrCodeFor(User $owner, bool $dynamic = true): QrCode
    {
        $qr = QrCode::create([
            'user_id' => $owner->id,
            'name' => 'Victim poster',
            'type' => 'url',
            'is_dynamic' => $dynamic,
            'content_data' => ['url' => 'https://victim.example.com'],
        ]);

        if ($dynamic) {
            ShortLink::create([
                'qr_code_id' => $qr->id,
                'slug' => ShortLink::generateSlug(),
                'destination_url' => 'https://victim.example.com',
                'is_active' => true,
            ]);
        }

        return $qr->fresh();
    }

    public function test_the_edit_route_is_closed_to_non_owners(): void
    {
        $qr = $this->qrCodeFor($this->user());

        $this->actingAs($this->user())
            ->get("/qr-codes/{$qr->id}/edit")
            ->assertForbidden();
    }

    public function test_the_builder_refuses_to_mount_another_users_qr_code(): void
    {
        $qr = $this->qrCodeFor($this->user());

        Livewire::actingAs($this->user())
            ->test(QrCodeBuilder::class, ['qrCode' => $qr])
            ->assertForbidden();
    }

    public function test_a_non_owner_cannot_rewrite_a_dynamic_destination(): void
    {
        $victim = $this->user();
        $attacker = $this->user();
        $qr = $this->qrCodeFor($victim);

        config(['qrcode.paid_action_stripe_price_id' => 'dev_free']);

        try {
            Livewire::actingAs($attacker)
                ->test(QrCodeBuilder::class, ['qrCode' => $qr])
                ->set('url', 'https://evil.example.com')
                ->call('save');
        } catch (\Throwable $e) {
            // Mounting is refused, which is the point.
        }

        $this->assertSame('https://victim.example.com', $qr->shortLink->fresh()->destination_url);
        $this->assertSame($victim->id, $qr->fresh()->user_id);
    }

    public function test_saving_an_edit_never_transfers_ownership(): void
    {
        $owner = $this->user();
        $qr = $this->qrCodeFor($owner, dynamic: false);

        Livewire::actingAs($owner)
            ->test(QrCodeBuilder::class, ['qrCode' => $qr])
            ->set('name', 'renamed by owner')
            ->set('url', 'https://owner.example.com')
            ->call('save');

        $this->assertSame($owner->id, $qr->fresh()->user_id);
        $this->assertSame('renamed by owner', $qr->fresh()->name);
    }

    public function test_analytics_refuses_another_users_qr_code(): void
    {
        $qr = $this->qrCodeFor($this->user());
        $attacker = $this->user();

        Livewire::actingAs($attacker)
            ->test(AnalyticsIndex::class, ['qrCode' => $qr])
            ->assertForbidden();
    }

    public function test_a_plain_member_cannot_manage_the_team(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $other = $this->user();

        $team = Team::create(['name' => 'Acme', 'owner_id' => $owner->id]);
        $team->users()->attach($owner->id, ['role' => 'owner']);
        $team->users()->attach($member->id, ['role' => 'member']);
        $team->users()->attach($other->id, ['role' => 'member']);
        $member->forceFill(['current_team_id' => $team->id])->save();

        $this->grantTeamsFeature($member);

        Livewire::actingAs($member)->test(TeamManager::class)
            ->call('updateRole', $other->id, 'admin')
            ->assertForbidden();

        Livewire::actingAs($member)->test(TeamManager::class)
            ->call('removeMember', $other->id)
            ->assertForbidden();

        Livewire::actingAs($member)->test(TeamManager::class)
            ->set('inviteEmail', 'someone@example.com')
            ->call('inviteMember')
            ->assertForbidden();

        $this->assertSame('member', $team->users()->whereKey($other->id)->first()->pivot->role);
        $this->assertTrue($team->users()->whereKey($other->id)->exists());
    }

    public function test_current_team_id_cannot_point_at_a_team_the_user_is_not_in(): void
    {
        $stranger = $this->user();
        $team = Team::create(['name' => 'Someone else', 'owner_id' => $this->user()->id]);

        $stranger->forceFill(['current_team_id' => $team->id])->save();

        $this->assertNull($stranger->fresh()->currentTeam());
    }

    private function grantTeamsFeature(User $user): void
    {
        \App\Models\Plan::updateOrCreate(['slug' => 'enterprise'], [
            'name' => 'Enterprise', 'price_monthly' => 3900, 'price_yearly' => 38900,
            'stripe_monthly_price_id' => 'dev_enterprise_monthly',
            'stripe_yearly_price_id' => 'dev_enterprise_yearly',
            'is_active' => true, 'sort_order' => 3,
        ]);

        $user->subscriptions()->create([
            'type' => 'default', 'stripe_id' => 'dev_'.uniqid(),
            'stripe_status' => 'active', 'stripe_price' => 'dev_enterprise_monthly',
            'quantity' => 1,
        ]);
    }
}
