<?php

namespace Database\Seeders;

use App\Enums\PlanTier;
use App\Enums\QrCodeType;
use App\Models\QrCode;
use App\Models\ShortLink;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Database\Seeder;

class MockUserSeeder extends Seeder
{
    public const PASSWORD = 'password';

    /**
     * @return array<int, array{name: string, email: string, role_label: string, plan: string, is_admin?: bool}>
     */
    public static function accounts(): array
    {
        return [
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'is_admin' => true,
                'plan' => 'enterprise',
                'role_label' => 'Administrator · Enterprise',
            ],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'plan' => 'pro',
                'role_label' => 'Pro plan',
            ],
            [
                'name' => 'Demo User',
                'email' => 'demo@example.com',
                'plan' => 'starter',
                'role_label' => 'Starter plan',
            ],
        ];
    }

    public static function emails(): array
    {
        return array_column(self::accounts(), 'email');
    }

    public function run(): void
    {
        foreach (self::accounts() as $account) {
            $this->seedAccount($account);
        }
    }

    /**
     * @param  array{name: string, email: string, role_label: string, plan: string, is_admin?: bool}  $account
     */
    private function seedAccount(array $account): User
    {
        $user = User::updateOrCreate(
            ['email' => $account['email']],
            [
                'name' => $account['name'],
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
                'is_admin' => $account['is_admin'] ?? false,
                'selected_plan' => $account['plan'],
                'plan_selected_at' => now(),
            ]
        );

        $this->applyPlan($user->fresh(), $account['plan']);

        if ($account['plan'] === 'pro') {
            $this->seedProUserQrCodes($user->fresh());
        }

        return $user->fresh();
    }

    private function seedProUserQrCodes(User $user): void
    {
        foreach ($this->proQrDefinitions() as $definition) {
            $qr = QrCode::firstOrCreate(
                ['user_id' => $user->id, 'name' => $definition['name']],
                [
                    'type' => $definition['type'],
                    'is_dynamic' => $definition['is_dynamic'],
                    'content_data' => $definition['content_data'],
                    'status' => 'active',
                    'total_scans' => 0,
                ]
            );

            $qr->design()->firstOrCreate(
                ['qr_code_id' => $qr->id],
                [
                    'fg_color' => '#000000',
                    'bg_color' => '#FFFFFF',
                    'dot_style' => 'square',
                    'eye_style' => 'square',
                    'eye_frame_style' => 'square',
                    'eye_ball_style' => 'square',
                ]
            );

            if ($definition['is_dynamic'] && ! $qr->shortLink) {
                ShortLink::create([
                    'qr_code_id' => $qr->id,
                    'slug' => ShortLink::generateSlug(),
                    'link_type' => $definition['link_type'] ?? 'redirect',
                    'destination_url' => $definition['destination_url'] ?? '',
                    'is_active' => true,
                ]);
            }
        }
    }

    /** @return array<int, array{name: string, type: QrCodeType, is_dynamic: bool, content_data: array, link_type?: string, destination_url?: string}> */
    private function proQrDefinitions(): array
    {
        return [
            // ── Dynamic types ────────────────────────────────────────────────
            [
                'name' => '[Demo] Website URL',
                'type' => QrCodeType::Url,
                'is_dynamic' => true,
                'content_data' => ['url' => 'https://example.com'],
                'link_type' => 'redirect',
                'destination_url' => 'https://example.com',
            ],
            [
                'name' => '[Demo] App Store',
                'type' => QrCodeType::AppStore,
                'is_dynamic' => true,
                'content_data' => ['url' => 'https://apps.apple.com/app/id123456789'],
                'link_type' => 'redirect',
                'destination_url' => 'https://apps.apple.com/app/id123456789',
            ],
            [
                'name' => '[Demo] Social Hub',
                'type' => QrCodeType::Social,
                'is_dynamic' => true,
                'content_data' => [
                    'hub_title' => 'Acme Demo',
                    'networks' => [
                        ['platform' => 'instagram', 'identifier' => 'acmedemo', 'url' => 'https://instagram.com/acmedemo'],
                        ['platform' => 'x',         'identifier' => 'acmedemo', 'url' => 'https://x.com/acmedemo'],
                        ['platform' => 'linkedin',   'identifier' => 'acme-demo', 'url' => 'https://linkedin.com/in/acme-demo'],
                    ],
                ],
                'link_type' => 'social_hub',
                'destination_url' => '',
            ],
            [
                'name' => '[Demo] PDF / File',
                'type' => QrCodeType::Pdf,
                'is_dynamic' => true,
                'content_data' => ['file_url' => 'https://example.com/brochure.pdf'],
                'link_type' => 'redirect',
                'destination_url' => 'https://example.com/brochure.pdf',
            ],
            [
                'name' => '[Demo] Restaurant Menu',
                'type' => QrCodeType::Menu,
                'is_dynamic' => true,
                'content_data' => ['url' => 'https://example.com/menu'],
                'link_type' => 'redirect',
                'destination_url' => 'https://example.com/menu',
            ],
            // ── Same dynamic types, static versions ──────────────────────────
            [
                'name' => '[Demo] Website URL (Static)',
                'type' => QrCodeType::Url,
                'is_dynamic' => false,
                'content_data' => ['url' => 'https://example.com'],
            ],
            [
                'name' => '[Demo] App Store (Static)',
                'type' => QrCodeType::AppStore,
                'is_dynamic' => false,
                'content_data' => ['url' => 'https://apps.apple.com/app/id123456789'],
            ],
            [
                'name' => '[Demo] Social (Static)',
                'type' => QrCodeType::Social,
                'is_dynamic' => false,
                'content_data' => [
                    'networks' => [
                        ['platform' => 'instagram', 'identifier' => 'acmedemo', 'url' => 'https://instagram.com/acmedemo'],
                    ],
                ],
            ],
            [
                'name' => '[Demo] PDF / File (Static)',
                'type' => QrCodeType::Pdf,
                'is_dynamic' => false,
                'content_data' => ['file_url' => 'https://example.com/brochure.pdf'],
            ],
            [
                'name' => '[Demo] Restaurant Menu (Static)',
                'type' => QrCodeType::Menu,
                'is_dynamic' => false,
                'content_data' => ['url' => 'https://example.com/menu'],
            ],
            // ── Static types ─────────────────────────────────────────────────
            [
                'name' => '[Demo] Plain Text',
                'type' => QrCodeType::Text,
                'is_dynamic' => false,
                'content_data' => ['text' => 'Welcome to our store! Show this QR at checkout for 10% off.'],
            ],
            [
                'name' => '[Demo] Contact Card',
                'type' => QrCodeType::VCard,
                'is_dynamic' => false,
                'content_data' => [
                    'first_name' => 'Jane',
                    'last_name'  => 'Smith',
                    'org'        => 'Acme Corp',
                    'title'      => 'Product Manager',
                    'phone'      => '+15551234567',
                    'email'      => 'jane.smith@acme.com',
                    'url'        => 'https://acme.com',
                    'address'    => '123 Main St, Springfield, IL 62701',
                ],
            ],
            [
                'name' => '[Demo] WiFi Network',
                'type' => QrCodeType::Wifi,
                'is_dynamic' => false,
                'content_data' => [
                    'ssid'       => 'GuestNetwork',
                    'password'   => 'Welcome2024',
                    'encryption' => 'WPA',
                    'hidden'     => false,
                ],
            ],
            [
                'name' => '[Demo] Email',
                'type' => QrCodeType::Email,
                'is_dynamic' => false,
                'content_data' => [
                    'email'   => 'support@example.com',
                    'subject' => 'Customer Inquiry',
                    'body'    => 'Hello, I would like to inquire about...',
                ],
            ],
            [
                'name' => '[Demo] Phone Number',
                'type' => QrCodeType::Phone,
                'is_dynamic' => false,
                'content_data' => ['phone' => '+15559876543'],
            ],
            [
                'name' => '[Demo] SMS Message',
                'type' => QrCodeType::Sms,
                'is_dynamic' => false,
                'content_data' => [
                    'phone'   => '+15559876543',
                    'message' => 'Hi! I scanned your QR code and wanted to reach out.',
                ],
            ],
            [
                'name' => '[Demo] Location',
                'type' => QrCodeType::Geo,
                'is_dynamic' => false,
                'content_data' => [
                    'latitude'  => '40.712776',
                    'longitude' => '-74.005974',
                ],
            ],
            [
                'name' => '[Demo] Calendar Event',
                'type' => QrCodeType::Event,
                'is_dynamic' => false,
                'content_data' => [
                    'title'       => 'Annual Product Launch',
                    'start'       => '20260901T090000Z',
                    'end'         => '20260901T180000Z',
                    'location'    => 'Convention Center, 500 W Madison St, Chicago, IL',
                    'description' => 'Join us for our biggest product launch of the year.',
                ],
            ],
            [
                'name' => '[Demo] Cryptocurrency',
                'type' => QrCodeType::Crypto,
                'is_dynamic' => false,
                'content_data' => [
                    'currency' => 'bitcoin',
                    'address'  => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
                    'amount'   => '0.001',
                ],
            ],
        ];
    }

    private function applyPlan(User $user, string $plan): void
    {
        $subscriptionService = app(SubscriptionService::class);

        if ($plan === 'starter') {
            $subscriptionService->downgradeToStarter($user);

            return;
        }

        try {
            $subscriptionService->subscribe($user, $plan, false);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'You are already on this plan.') {
                throw $e;
            }
        }
    }

    public static function expectedPlanTier(string $email): PlanTier
    {
        foreach (self::accounts() as $account) {
            if ($account['email'] === $email) {
                return PlanTier::from($account['plan']);
            }
        }

        return PlanTier::Starter;
    }
}
