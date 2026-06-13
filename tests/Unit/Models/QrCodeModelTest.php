<?php

namespace Tests\Unit\Models;

use App\Enums\QrCodeType;
use App\Models\QrCode;
use App\Models\ShortLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_encoded_content_for_static_url(): void
    {
        $qrCode = QrCode::factory()->make([
            'type' => QrCodeType::Url,
            'is_dynamic' => false,
            'content_data' => ['url' => 'https://example.com'],
        ]);

        $this->assertSame('https://example.com', $qrCode->getEncodedContent());
    }

    public function test_get_encoded_content_for_dynamic_url_uses_proxy_url(): void
    {
        $qrCode = QrCode::factory()->create([
            'type' => QrCodeType::Url,
            'is_dynamic' => true,
            'content_data' => ['url' => 'https://example.com'],
        ]);

        ShortLink::factory()->create([
            'qr_code_id' => $qrCode->id,
            'slug' => 'abc1234',
            'destination_url' => 'https://example.com',
        ]);

        $qrCode->load('shortLink');

        $this->assertSame(
            config('app.proxy_scheme', 'https') . '://' . config('app.proxy_domain') . '/abc1234',
            $qrCode->getEncodedContent()
        );
    }

    public function test_get_encoded_content_for_vcard(): void
    {
        $qrCode = QrCode::factory()->make([
            'type' => QrCodeType::VCard,
            'content_data' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane@example.com',
            ],
        ]);

        $content = $qrCode->getEncodedContent();

        $this->assertStringContainsString('BEGIN:VCARD', $content);
        $this->assertStringContainsString('Jane', $content);
        $this->assertStringContainsString('EMAIL:jane@example.com', $content);
    }

    public function test_get_encoded_content_for_wifi(): void
    {
        $qrCode = QrCode::factory()->make([
            'type' => QrCodeType::Wifi,
            'content_data' => [
                'ssid' => 'MyNetwork',
                'password' => 'secret',
                'encryption' => 'WPA',
                'hidden' => false,
            ],
        ]);

        $this->assertSame(
            'WIFI:T:WPA;S:MyNetwork;P:secret;H:false;;',
            $qrCode->getEncodedContent()
        );
    }

    public function test_get_encoded_content_for_email(): void
    {
        $qrCode = QrCode::factory()->make([
            'type' => QrCodeType::Email,
            'content_data' => [
                'email' => 'hello@example.com',
                'subject' => 'Hi',
                'body' => 'There',
            ],
        ]);

        $this->assertSame(
            'mailto:hello@example.com?subject=Hi&body=There',
            $qrCode->getEncodedContent()
        );
    }

    public function test_get_encoded_content_for_geo(): void
    {
        $qrCode = QrCode::factory()->make([
            'type' => QrCodeType::Geo,
            'content_data' => [
                'latitude' => '48.8566',
                'longitude' => '2.3522',
            ],
        ]);

        $this->assertSame('geo:48.8566,2.3522', $qrCode->getEncodedContent());
    }

    public function test_get_encoded_content_for_static_social_uses_first_network_url(): void
    {
        $qrCode = QrCode::factory()->make([
            'type' => QrCodeType::Social,
            'is_dynamic' => false,
            'content_data' => [
                'networks' => [
                    [
                        'platform' => 'instagram',
                        'identifier' => 'johndoe',
                        'url' => 'https://instagram.com/johndoe',
                    ],
                ],
            ],
        ]);

        $this->assertSame('https://instagram.com/johndoe', $qrCode->getEncodedContent());
    }

    public function test_get_card_display_url_for_static_url(): void
    {
        $qrCode = QrCode::factory()->make([
            'type' => QrCodeType::Url,
            'is_dynamic' => false,
            'content_data' => ['url' => 'https://example.com'],
        ]);

        $this->assertSame('https://example.com', $qrCode->getCardDisplayUrl());
    }

    public function test_get_card_display_url_for_dynamic_url_uses_short_link(): void
    {
        $qrCode = QrCode::factory()->create([
            'type' => QrCodeType::Url,
            'is_dynamic' => true,
            'content_data' => ['url' => 'https://example.com'],
        ]);

        ShortLink::factory()->create([
            'qr_code_id' => $qrCode->id,
            'slug' => 'abc1234',
            'destination_url' => 'https://example.com',
        ]);

        $qrCode->load('shortLink');

        $this->assertSame(
            config('app.proxy_scheme', 'https') . '://' . config('app.proxy_domain') . '/abc1234',
            $qrCode->getCardDisplayUrl()
        );
    }

    public function test_get_card_display_url_for_vcard_shows_preview(): void
    {
        $qrCode = QrCode::factory()->make([
            'type' => QrCodeType::VCard,
            'is_dynamic' => false,
            'content_data' => [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'org' => 'Acme Corp',
                'title' => 'Product Manager',
                'email' => 'jane.smith@acme.com',
            ],
        ]);

        $this->assertSame(
            'Jane Smith · Product Manager · Acme Corp · jane.smith@acme.com',
            $qrCode->getCardDisplayUrl()
        );
    }

    public function test_get_content_fields_for_vcard(): void
    {
        $qrCode = QrCode::factory()->make([
            'type' => QrCodeType::VCard,
            'is_dynamic' => false,
            'content_data' => [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@acme.com',
            ],
        ]);

        $fields = $qrCode->getContentFields();

        $this->assertSame('Jane', $fields[0]['value']);
        $this->assertSame('Smith', $fields[1]['value']);
        $this->assertSame('jane.smith@acme.com', $fields[2]['value']);
    }

    public function test_get_card_copy_value_for_vcard_uses_encoded_content(): void
    {
        $qrCode = QrCode::factory()->make([
            'type' => QrCodeType::VCard,
            'is_dynamic' => false,
            'content_data' => [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
            ],
        ]);

        $this->assertStringContainsString('BEGIN:VCARD', $qrCode->getCardCopyValue());
        $this->assertStringContainsString('Jane', $qrCode->getCardCopyValue());
    }
}
