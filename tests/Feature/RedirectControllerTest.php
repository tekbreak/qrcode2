<?php

namespace Tests\Feature;

use App\Jobs\RecordScanJob;
use App\Models\QrCode;
use App\Models\ShortLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RedirectControllerTest extends TestCase
{
    use RefreshDatabase;

    private function getRedirect(string $slug, array $query = []): \Illuminate\Testing\TestResponse
    {
        $domain = config('app.proxy_domain');
        $url = 'http://'.$domain.'/'.$slug;

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $this->get($url);
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->getRedirect('missing')
            ->assertNotFound();
    }

    public function test_expired_link_returns_410(): void
    {
        $link = ShortLink::factory()->expired()->create(['slug' => 'expired1']);

        $this->getRedirect($link->slug)
            ->assertStatus(410);
    }

    public function test_max_scans_limit_returns_410(): void
    {
        $qrCode = QrCode::factory()->create(['total_scans' => 5]);
        $link = ShortLink::factory()->maxScans(5)->create([
            'qr_code_id' => $qrCode->id,
            'slug' => 'maxed1',
        ]);

        $this->getRedirect($link->slug)
            ->assertStatus(410);
    }

    public function test_password_protected_link_shows_form_without_password(): void
    {
        $link = ShortLink::factory()->passwordProtected()->create(['slug' => 'protected1']);

        $this->getRedirect($link->slug)
            ->assertOk()
            ->assertViewIs('redirect.password');
    }

    public function test_password_protected_link_rejects_invalid_password(): void
    {
        $link = ShortLink::factory()->passwordProtected('secret')->create(['slug' => 'protected2']);

        $this->getRedirect($link->slug, ['password' => 'wrong'])
            ->assertStatus(403);
    }

    public function test_successful_redirect_dispatches_scan_job(): void
    {
        Queue::fake();

        $link = ShortLink::factory()->create([
            'destination_url' => 'https://destination.test',
            'slug' => 'redirect1',
        ]);

        $this->getRedirect($link->slug)
            ->assertRedirect('https://destination.test');

        Queue::assertPushed(RecordScanJob::class, function (RecordScanJob $job) use ($link) {
            return $job->shortLinkId === $link->id
                && $job->qrCodeId === $link->qr_code_id;
        });
    }

    public function test_social_hub_renders_landing_page(): void
    {
        Queue::fake();

        $qrCode = QrCode::factory()->create([
            'type' => 'social',
            'is_dynamic' => true,
            'content_data' => [
                'hub_title' => 'John Doe',
                'networks' => [
                    [
                        'platform' => 'instagram',
                        'identifier' => 'johndoe',
                        'url' => 'https://instagram.com/johndoe',
                    ],
                    [
                        'platform' => 'tiktok',
                        'identifier' => 'johndoe',
                        'url' => 'https://tiktok.com/@johndoe',
                    ],
                ],
            ],
        ]);

        $link = ShortLink::factory()->create([
            'qr_code_id' => $qrCode->id,
            'slug' => 'socialhub1',
            'link_type' => 'social_hub',
            'destination_url' => '',
        ]);

        $this->getRedirect($link->slug)
            ->assertOk()
            ->assertViewIs('redirect.social-hub')
            ->assertSee('John Doe')
            ->assertSee('Instagram')
            ->assertSee('TikTok')
            ->assertDontSee('Tap a profile to connect')
            ->assertDontSee('Powered by QR Code App');

        Queue::assertPushed(RecordScanJob::class);
    }
}
