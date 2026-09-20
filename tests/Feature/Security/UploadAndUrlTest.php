<?php

namespace Tests\Feature\Security;

use App\Livewire\QrCodes\QrCodeBuilder;
use App\Models\Plan;
use App\Models\QrCode;
use App\Models\QrDesign;
use App\Models\ShortLink;
use App\Models\User;
use App\Support\Url;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UploadAndUrlTest extends TestCase
{
    use RefreshDatabase;

    private function proUser(): User
    {
        $user = User::factory()->create();

        Plan::updateOrCreate(['slug' => 'pro'], [
            'name' => 'Pro', 'price_monthly' => 1000, 'price_yearly' => 9900,
            'stripe_monthly_price_id' => 'dev_pro_monthly',
            'stripe_yearly_price_id' => 'dev_pro_yearly',
            'is_active' => true, 'sort_order' => 2,
        ]);

        $user->subscriptions()->create([
            'type' => 'default', 'stripe_id' => 'dev_'.uniqid(),
            'stripe_status' => 'active', 'stripe_price' => 'dev_pro_monthly', 'quantity' => 1,
        ]);

        return $user->fresh();
    }

    #[DataProvider('unsafeUrls')]
    public function test_unsafe_urls_are_rejected_by_the_helper(string $url): void
    {
        $this->assertFalse(Url::isSafe($url), "expected {$url} to be rejected");
    }

    public static function unsafeUrls(): array
    {
        return [
            ['javascript:alert(1)'],
            ['JaVaScRiPt:alert(1)'],
            [' javascript:alert(1)'],
            ["java\nscript:alert(1)"],
            ['data:text/html,<script>alert(1)</script>'],
            ['vbscript:msgbox(1)'],
            ['//evil.example.com'],
            ['ftp://example.com'],
        ];
    }

    public function test_the_builder_rejects_a_javascript_social_url(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(QrCodeBuilder::class)
            ->set('type', 'social')
            ->set('name', 'hub')
            ->set('isDynamic', true)
            ->set('socialHubTitle', 'My links')
            ->set('socialNetworks', [
                ['platform' => 'custom', 'identifier' => 'javascript:alert(1)', 'url' => 'javascript:alert(1)'],
                ['platform' => 'custom', 'identifier' => 'https://ok.example.com', 'url' => 'https://ok.example.com'],
            ])
            ->call('save')
            ->assertHasErrors('socialNetworks.0.url');

        $this->assertDatabaseCount('qr_codes', 0);
    }

    public function test_the_builder_rejects_a_javascript_destination(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(QrCodeBuilder::class)
            ->set('type', 'url')
            ->set('name', 'x')
            ->set('url', 'javascript:alert(1)')
            ->call('save')
            ->assertHasErrors('url');
    }

    public function test_the_hub_page_never_renders_an_unsafe_href(): void
    {
        $user = User::factory()->create();

        // A row poisoned before the fix existed.
        $qr = QrCode::create([
            'user_id' => $user->id, 'name' => 'legacy hub', 'type' => 'social',
            'is_dynamic' => true,
            'content_data' => ['hub_title' => 'Links', 'networks' => [
                ['platform' => 'custom', 'identifier' => 'a', 'url' => 'javascript:alert(document.domain)'],
                ['platform' => 'custom', 'identifier' => 'b', 'url' => 'https://ok.example.com'],
            ]],
        ]);
        $link = ShortLink::create([
            'qr_code_id' => $qr->id, 'slug' => ShortLink::generateSlug(),
            'link_type' => 'social_hub', 'destination_url' => '', 'is_active' => true,
        ]);

        $body = $this->get("http://go.localhost/{$link->slug}")->assertOk()->getContent();

        $this->assertStringNotContainsString('javascript:', $body);
        $this->assertStringContainsString('https://ok.example.com', $body);
    }

    public function test_a_poisoned_redirect_destination_is_refused(): void
    {
        $user = User::factory()->create();
        $qr = QrCode::create([
            'user_id' => $user->id, 'name' => 'legacy', 'type' => 'url',
            'is_dynamic' => true, 'content_data' => ['url' => 'javascript:alert(1)'],
        ]);
        $link = ShortLink::create([
            'qr_code_id' => $qr->id, 'slug' => ShortLink::generateSlug(),
            'destination_url' => 'javascript:alert(1)', 'is_active' => true,
        ]);

        $this->get("http://go.localhost/{$link->slug}")->assertStatus(410);
    }

    public function test_dangerous_logo_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $files = [
            'shell.php' => UploadedFile::fake()->createWithContent('shell.php', "<?php echo 'rce';"),
            'logo.svg' => UploadedFile::fake()->createWithContent('logo.svg', '<svg onload="alert(1)"></svg>'),
            'page.html' => UploadedFile::fake()->createWithContent('page.html', '<script>alert(1)</script>'),
        ];

        foreach ($files as $name => $file) {
            Livewire::actingAs($user)->test(QrCodeBuilder::class)
                ->set('type', 'url')->set('name', "logo-{$name}")
                ->set('url', 'https://example.com')
                ->set('logo', $file)
                ->call('save')
                ->assertHasErrors('logo');
        }

        $this->assertEmpty(Storage::disk('public')->allFiles('logos'));
    }

    public function test_a_valid_logo_is_still_accepted(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(QrCodeBuilder::class)
            ->set('type', 'url')->set('name', 'with logo')
            ->set('url', 'https://example.com')
            ->set('logo', UploadedFile::fake()->image('logo.png', 200, 200))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNotEmpty(Storage::disk('public')->allFiles('logos'));
    }

    public function test_an_oversized_logo_is_rejected(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->create())->test(QrCodeBuilder::class)
            ->set('type', 'url')->set('name', 'big')
            ->set('url', 'https://example.com')
            ->set('logo', UploadedFile::fake()->image('huge.png', 4000, 4000))
            ->call('save')
            ->assertHasErrors('logo');
    }

    public function test_non_pdf_uploads_are_rejected_for_pdf_codes(): void
    {
        Storage::fake('public');

        Livewire::actingAs(User::factory()->create())->test(QrCodeBuilder::class)
            ->set('type', 'pdf')->set('name', 'doc')
            ->set('pdfFile', UploadedFile::fake()->createWithContent('doc.php', '<?php'))
            ->call('save')
            ->assertHasErrors('pdfFile');
    }

    public function test_the_api_cannot_set_a_logo_path(): void
    {
        $user = $this->proUser();
        $qr = QrCode::create([
            'user_id' => $user->id, 'name' => 'q', 'type' => 'url',
            'is_dynamic' => false, 'content_data' => ['url' => 'https://example.com'],
        ]);
        QrDesign::create(['qr_code_id' => $qr->id, 'fg_color' => '#000000', 'bg_color' => '#FFFFFF']);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/qr-codes/{$qr->id}", ['design' => ['logo_path' => '/etc/passwd']])
            ->assertStatus(422);

        $this->assertNull($qr->design()->first()->logo_path);
    }

    public function test_the_api_rejects_a_javascript_destination(): void
    {
        $user = $this->proUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/qr-codes', [
                'name' => 'x', 'type' => 'url',
                'content_data' => ['url' => 'javascript:alert(1)'],
            ])
            ->assertStatus(422);
    }

    public function test_api_pagination_and_render_size_are_bounded(): void
    {
        $user = $this->proUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/qr-codes?per_page=1000000')
            ->assertOk()
            ->assertJsonPath('per_page', 100);

        $qr = QrCode::create([
            'user_id' => $user->id, 'name' => 'q', 'type' => 'url',
            'is_dynamic' => false, 'content_data' => ['url' => 'https://example.com'],
        ]);
        QrDesign::create(['qr_code_id' => $qr->id, 'fg_color' => '#000000', 'bg_color' => '#FFFFFF']);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/qr-codes/{$qr->id}/download?size=20000")
            ->assertStatus(422);
    }
}
