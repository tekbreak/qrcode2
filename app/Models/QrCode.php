<?php

namespace App\Models;

use App\Enums\QrCodeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

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
            QrCodeType::AppStore, QrCodeType::Social, QrCodeType::Menu =>
                $this->is_dynamic ? ($this->getProxyUrl() ?? '') : ($this->content_data['url'] ?? ''),
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
}
