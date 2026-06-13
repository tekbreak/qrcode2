<?php

namespace App\Models;

use App\Enums\QrCodeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class QrCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'team_id',
        'name',
        'type',
        'is_dynamic',
        'content_data',
        'status',
        'total_scans',
    ];

    protected function casts(): array
    {
        return [
            'type' => QrCodeType::class,
            'is_dynamic' => 'boolean',
            'content_data' => 'array',
            'total_scans' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function design(): HasOne
    {
        return $this->hasOne(QrDesign::class);
    }

    public function shortLink(): HasOne
    {
        return $this->hasOne(ShortLink::class);
    }

    public function shortLinks(): HasMany
    {
        return $this->hasMany(ShortLink::class);
    }

    public function getProxyUrl(): ?string
    {
        $link = $this->shortLink;
        if (! $link) {
            return null;
        }

        $domain = $link->domain ?: config('app.proxy_domain');
        $scheme = config('app.proxy_scheme', 'https');

        return "{$scheme}://{$domain}/{$link->slug}";
    }

    public function getCardDisplayUrl(): ?string
    {
        if ($this->is_dynamic && $this->shortLink) {
            return $this->shortLink->getFullUrl();
        }

        $preview = $this->buildCardPreview();

        return $preview !== '' ? Str::limit($preview, 80) : null;
    }

    public function getCardCopyValue(): string
    {
        if ($this->is_dynamic && $this->shortLink) {
            return $this->shortLink->getFullUrl();
        }

        return $this->getEncodedContent();
    }

    /** @return array<int, array{label: string, value: string}> */
    public function getContentFields(): array
    {
        $d = $this->content_data ?? [];

        $fields = match ($this->type) {
            QrCodeType::Url, QrCodeType::AppStore, QrCodeType::Menu => [
                $this->contentField(__('qr.url'), $d['url'] ?? ''),
            ],
            QrCodeType::Pdf => [
                $this->contentField(__('qr.fields.file_url'), $d['file_url'] ?? ''),
            ],
            QrCodeType::Social => collect($d['networks'] ?? [])
                ->map(fn (array $network) => $this->contentField(
                    ucfirst($network['platform'] ?? __('qr.type')),
                    $network['url'] ?? ''
                ))
                ->all(),
            QrCodeType::Text => [
                $this->contentField(__('qr.text'), $d['text'] ?? ''),
            ],
            QrCodeType::VCard => [
                $this->contentField(__('qr.fields.first_name'), $d['first_name'] ?? ''),
                $this->contentField(__('qr.fields.last_name'), $d['last_name'] ?? ''),
                $this->contentField(__('qr.fields.organization'), $d['org'] ?? ''),
                $this->contentField(__('qr.fields.title'), $d['title'] ?? ''),
                $this->contentField(__('qr.fields.phone'), $d['phone'] ?? ''),
                $this->contentField(__('qr.fields.email'), $d['email'] ?? ''),
                $this->contentField(__('qr.fields.website'), $d['url'] ?? ''),
                $this->contentField(__('qr.fields.address'), $d['address'] ?? ''),
            ],
            QrCodeType::Wifi => [
                $this->contentField(__('qr.fields.ssid'), $d['ssid'] ?? ''),
                $this->contentField(__('qr.fields.password'), $d['password'] ?? ''),
                $this->contentField(__('qr.fields.encryption'), $d['encryption'] ?? ''),
                $this->contentField(__('qr.fields.hidden_network'), ($d['hidden'] ?? false) ? __('common.yes') : __('common.no')),
            ],
            QrCodeType::Email => [
                $this->contentField(__('qr.fields.email'), $d['email'] ?? ''),
                $this->contentField(__('qr.fields.subject'), $d['subject'] ?? ''),
                $this->contentField(__('qr.fields.message'), $d['body'] ?? ''),
            ],
            QrCodeType::Phone => [
                $this->contentField(__('qr.fields.phone'), $d['phone'] ?? ''),
            ],
            QrCodeType::Sms => [
                $this->contentField(__('qr.fields.phone'), $d['phone'] ?? ''),
                $this->contentField(__('qr.fields.message'), $d['message'] ?? ''),
            ],
            QrCodeType::Geo => [
                $this->contentField(__('qr.fields.latitude'), $d['latitude'] ?? ''),
                $this->contentField(__('qr.fields.longitude'), $d['longitude'] ?? ''),
            ],
            QrCodeType::Event => [
                $this->contentField(__('qr.fields.event_title'), $d['title'] ?? ''),
                $this->contentField(__('qr.fields.start'), $d['start'] ?? ''),
                $this->contentField(__('qr.fields.end'), $d['end'] ?? ''),
                $this->contentField(__('qr.fields.location'), $d['location'] ?? ''),
                $this->contentField(__('qr.fields.description'), $d['description'] ?? ''),
            ],
            QrCodeType::Crypto => [
                $this->contentField(__('qr.fields.currency'), $d['currency'] ?? ''),
                $this->contentField(__('qr.fields.wallet_address'), $d['address'] ?? ''),
                $this->contentField(__('qr.fields.amount'), $d['amount'] ?? ''),
            ],
            default => [
                $this->contentField(__('qr.url'), $d['url'] ?? ''),
                $this->contentField(__('qr.text'), $d['text'] ?? ''),
            ],
        };

        return array_values(array_filter($fields));
    }

    protected function buildCardPreview(): string
    {
        $d = $this->content_data ?? [];

        return match ($this->type) {
            QrCodeType::Url, QrCodeType::AppStore, QrCodeType::Menu => $d['url'] ?? '',
            QrCodeType::Pdf => $d['file_url'] ?? '',
            QrCodeType::Social => $this->getSocialStaticUrl(),
            QrCodeType::Text => $d['text'] ?? '',
            QrCodeType::VCard => $this->buildVCardPreview(),
            QrCodeType::Wifi => $this->buildWifiPreview(),
            QrCodeType::Email => $d['email'] ?? '',
            QrCodeType::Phone => $d['phone'] ?? '',
            QrCodeType::Sms => trim(($d['phone'] ?? '') . (($d['message'] ?? '') !== '' ? ': ' . $d['message'] : '')),
            QrCodeType::Geo => trim(($d['latitude'] ?? '') . ', ' . ($d['longitude'] ?? ''), ', '),
            QrCodeType::Event => $d['title'] ?? '',
            QrCodeType::Crypto => trim(ucfirst($d['currency'] ?? 'bitcoin') . ': ' . ($d['address'] ?? '')),
            default => $d['url'] ?? $d['text'] ?? '',
        };
    }

    protected function buildVCardPreview(): string
    {
        $d = $this->content_data ?? [];
        $name = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));

        return implode(' · ', array_filter([
            $name !== '' ? $name : null,
            $d['title'] ?? null,
            $d['org'] ?? null,
            $d['email'] ?? null,
        ]));
    }

    protected function buildWifiPreview(): string
    {
        $d = $this->content_data ?? [];
        $ssid = $d['ssid'] ?? '';

        if ($ssid === '') {
            return '';
        }

        return $ssid . ' (' . ($d['encryption'] ?? 'WPA') . ')';
    }

    /** @return array{label: string, value: string}|null */
    protected function contentField(string $label, mixed $value): ?array
    {
        $value = is_string($value) ? trim($value) : (string) $value;

        if ($value === '') {
            return null;
        }

        return ['label' => $label, 'value' => $value];
    }

    public function getEncodedContent(): string
    {
        return match ($this->type) {
            QrCodeType::Url => $this->is_dynamic ? $this->getProxyUrl() : ($this->content_data['url'] ?? ''),
            QrCodeType::Text => $this->content_data['text'] ?? '',
            QrCodeType::VCard => $this->buildVCard(),
            QrCodeType::Wifi => $this->buildWifi(),
            QrCodeType::Email => $this->buildEmail(),
            QrCodeType::Phone => 'tel:' . ($this->content_data['phone'] ?? ''),
            QrCodeType::Sms => $this->buildSms(),
            QrCodeType::Geo => $this->buildGeo(),
            QrCodeType::Event => $this->buildEvent(),
            QrCodeType::Crypto => $this->buildCrypto(),
            QrCodeType::AppStore, QrCodeType::Menu =>
                $this->is_dynamic ? ($this->getProxyUrl() ?? '') : ($this->content_data['url'] ?? ''),
            QrCodeType::Social =>
                $this->is_dynamic ? ($this->getProxyUrl() ?? '') : $this->getSocialStaticUrl(),
            QrCodeType::Pdf => $this->is_dynamic ? ($this->getProxyUrl() ?? '') : ($this->content_data['file_url'] ?? ''),
            default => $this->is_dynamic ? ($this->getProxyUrl() ?? '') : ($this->content_data['url'] ?? $this->content_data['text'] ?? ''),
        };
    }

    protected function buildVCard(): string
    {
        $d = $this->content_data;
        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:' . ($d['last_name'] ?? '') . ';' . ($d['first_name'] ?? ''),
            'FN:' . ($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''),
        ];
        if (! empty($d['org'])) $lines[] = 'ORG:' . $d['org'];
        if (! empty($d['title'])) $lines[] = 'TITLE:' . $d['title'];
        if (! empty($d['phone'])) $lines[] = 'TEL:' . $d['phone'];
        if (! empty($d['email'])) $lines[] = 'EMAIL:' . $d['email'];
        if (! empty($d['url'])) $lines[] = 'URL:' . $d['url'];
        if (! empty($d['address'])) $lines[] = 'ADR:;;' . $d['address'];
        $lines[] = 'END:VCARD';

        return implode("\n", $lines);
    }

    protected function buildWifi(): string
    {
        $d = $this->content_data;
        $encryption = $d['encryption'] ?? 'WPA';
        $ssid = $d['ssid'] ?? '';
        $password = $d['password'] ?? '';
        $hidden = ($d['hidden'] ?? false) ? 'true' : 'false';

        return "WIFI:T:{$encryption};S:{$ssid};P:{$password};H:{$hidden};;";
    }

    protected function buildEmail(): string
    {
        $d = $this->content_data;
        $email = $d['email'] ?? '';
        $subject = $d['subject'] ?? '';
        $body = $d['body'] ?? '';

        return "mailto:{$email}?subject=" . rawurlencode($subject) . "&body=" . rawurlencode($body);
    }

    protected function buildSms(): string
    {
        $d = $this->content_data;
        return "sms:{$d['phone']}?body=" . rawurlencode($d['message'] ?? '');
    }

    protected function buildGeo(): string
    {
        $d = $this->content_data;
        return "geo:{$d['latitude']},{$d['longitude']}";
    }

    protected function buildEvent(): string
    {
        $d = $this->content_data;
        $lines = [
            'BEGIN:VEVENT',
            'SUMMARY:' . ($d['title'] ?? ''),
            'DTSTART:' . ($d['start'] ?? ''),
            'DTEND:' . ($d['end'] ?? ''),
        ];
        if (! empty($d['location'])) $lines[] = 'LOCATION:' . $d['location'];
        if (! empty($d['description'])) $lines[] = 'DESCRIPTION:' . $d['description'];
        $lines[] = 'END:VEVENT';

        return "BEGIN:VCALENDAR\nVERSION:2.0\n" . implode("\n", $lines) . "\nEND:VCALENDAR";
    }

    protected function buildCrypto(): string
    {
        $d = $this->content_data;
        $currency = strtolower($d['currency'] ?? 'bitcoin');
        $address = $d['address'] ?? '';
        $amount = $d['amount'] ?? '';

        $uri = "{$currency}:{$address}";
        if ($amount) {
            $uri .= "?amount={$amount}";
        }
        return $uri;
    }

    protected function getSocialStaticUrl(): string
    {
        $networks = $this->content_data['networks'] ?? [];

        if (! empty($networks)) {
            return $networks[0]['url'] ?? '';
        }

        return $this->content_data['url'] ?? '';
    }
}
