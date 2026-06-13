<?php

namespace App\Jobs;

use App\Models\QrCode;
use App\Models\Scan;
use App\Models\ShortLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $shortLinkId,
        public int $qrCodeId,
        public ?int $userId,
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $referrer,
    ) {}

    public function handle(): void
    {
        $ipHash = $this->ipAddress ? hash('sha256', $this->ipAddress . $this->shortLinkId) : null;

        $isUnique = $ipHash && ! Scan::where('short_link_id', $this->shortLinkId)
            ->where('ip_hash', $ipHash)
            ->where('scanned_at', '>=', now()->subDay())
            ->exists();

        $parsed = $this->parseUserAgent($this->userAgent);

        Scan::create([
            'short_link_id' => $this->shortLinkId,
            'ip_hash' => $ipHash,
            'device_type' => $parsed['device_type'],
            'os' => $parsed['os'],
            'browser' => $parsed['browser'],
            'referrer' => $this->referrer,
            'is_unique' => $isUnique,
            'scanned_at' => now(),
        ]);

        QrCode::where('id', $this->qrCodeId)->increment('total_scans');
    }

    protected function parseUserAgent(?string $ua): array
    {
        if (! $ua) {
            return ['device_type' => 'unknown', 'os' => 'unknown', 'browser' => 'unknown'];
        }

        $ua = strtolower($ua);

        $device_type = 'desktop';
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android')) {
            $device_type = 'mobile';
        } elseif (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) {
            $device_type = 'tablet';
        }

        $os = 'other';
        if (str_contains($ua, 'windows')) $os = 'Windows';
        elseif (str_contains($ua, 'mac os') || str_contains($ua, 'macintosh')) $os = 'macOS';
        elseif (str_contains($ua, 'iphone') || str_contains($ua, 'ipad')) $os = 'iOS';
        elseif (str_contains($ua, 'android')) $os = 'Android';
        elseif (str_contains($ua, 'linux')) $os = 'Linux';

        $browser = 'other';
        if (str_contains($ua, 'edg/')) $browser = 'Edge';
        elseif (str_contains($ua, 'chrome') && ! str_contains($ua, 'chromium')) $browser = 'Chrome';
        elseif (str_contains($ua, 'firefox')) $browser = 'Firefox';
        elseif (str_contains($ua, 'safari') && ! str_contains($ua, 'chrome')) $browser = 'Safari';
        elseif (str_contains($ua, 'opera') || str_contains($ua, 'opr/')) $browser = 'Opera';

        return compact('device_type', 'os', 'browser');
    }
}
