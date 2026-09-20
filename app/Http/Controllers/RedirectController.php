<?php

namespace App\Http\Controllers;

use App\Jobs\RecordScanJob;
use App\Livewire\QrCodes\QrCodeBuilder;
use App\Models\QrCode;
use App\Models\ShortLink;
use App\Support\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class RedirectController extends Controller
{
    public function handle(Request $request, string $slug)
    {
        $linkData = $this->lookup($slug);

        if (! $linkData) {
            abort(404);
        }

        if ($response = $this->guardAvailability($linkData, $slug)) {
            return $response;
        }

        if ($linkData['is_password_protected'] && ! $this->isUnlocked($request, $slug)) {
            return $this->passwordPrompt($slug);
        }

        RecordScanJob::dispatch(
            shortLinkId: $linkData['id'],
            qrCodeId: $linkData['qr_code_id'],
            userId: $linkData['qr_user_id'],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            referrer: $request->header('referer'),
        );

        if (($linkData['link_type'] ?? 'redirect') === 'social_hub') {
            return response()->view('redirect.social-hub', [
                'networks' => $linkData['networks'],
                'hub_title' => $linkData['hub_title'] ?? '',
                'platforms' => QrCodeBuilder::socialPlatforms(),
            ], 200, ['Referrer-Policy' => 'no-referrer']);
        }

        $destination = Url::safeOrNull($linkData['destination_url']);

        if ($destination === null) {
            abort(410, 'This link has no valid destination.');
        }

        return redirect()->away($destination, 302, ['Referrer-Policy' => 'no-referrer']);
    }

    /**
     * Verify a link password. Submitted over POST so it never lands in access
     * logs, browser history or the Referer header, and rate limited so it cannot
     * be guessed at speed.
     */
    public function unlock(Request $request, string $slug)
    {
        $request->validate(['password' => 'required|string|max:255']);

        $linkData = $this->lookup($slug);

        if (! $linkData || ! $linkData['is_password_protected']) {
            abort(404);
        }

        if ($response = $this->guardAvailability($linkData, $slug)) {
            return $response;
        }

        // Keyed on slug and IP together so one link under attack cannot lock out
        // everybody else scanning it.
        $throttleKey = 'link-unlock|'.$slug.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            return $this->passwordPrompt($slug, __('qr.too_many_attempts'), 429);
        }

        $hash = ShortLink::where('slug', $slug)->value('password_hash');

        if (! $hash || ! Hash::check($request->input('password'), $hash)) {
            RateLimiter::hit($throttleKey, 300);

            return $this->passwordPrompt($slug, __('qr.invalid_password'), 403);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->put($this->unlockKey($slug), true);

        return redirect()->route('redirect.handle', $slug);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function lookup(string $slug): ?array
    {
        $linkData = Cache::remember("shortlink:{$slug}", 300, function () use ($slug) {
            $link = ShortLink::with('qrCode')
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (! $link) {
                return false;
            }

            $contentData = $link->qrCode?->content_data ?? [];

            return [
                'id' => $link->id,
                'destination_url' => $link->destination_url,
                'link_type' => $link->link_type ?? 'redirect',
                'networks' => $contentData['networks'] ?? [],
                'hub_title' => $contentData['hub_title'] ?? '',
                'qr_code_id' => $link->qr_code_id,
                'rules' => $link->rules,
                // Only whether a password is set - the hash itself stays out of
                // the shared cache and is read on an unlock attempt.
                'is_password_protected' => filled($link->password_hash),
                'expires_at' => $link->expires_at?->toIso8601String(),
                'max_scans' => $link->max_scans,
                'qr_user_id' => $link->qrCode?->user_id,
            ];
        });

        return $linkData === false ? null : $linkData;
    }

    /**
     * @param  array<string, mixed>  $linkData
     */
    protected function guardAvailability(array $linkData, string $slug): mixed
    {
        if ($linkData['expires_at'] && now()->isAfter($linkData['expires_at'])) {
            Cache::forget("shortlink:{$slug}");
            abort(410, 'This link has expired.');
        }

        if ($linkData['max_scans']) {
            $totalScans = QrCode::where('id', $linkData['qr_code_id'])->value('total_scans') ?? 0;

            if ($totalScans >= $linkData['max_scans']) {
                Cache::forget("shortlink:{$slug}");
                abort(410, 'This link has reached its scan limit.');
            }
        }

        return null;
    }

    protected function passwordPrompt(string $slug, ?string $error = null, int $status = 200)
    {
        return response()->view('redirect.password', [
            'slug' => $slug,
            'error' => $error,
        ], $status, ['Referrer-Policy' => 'no-referrer']);
    }

    protected function isUnlocked(Request $request, string $slug): bool
    {
        return (bool) $request->session()->get($this->unlockKey($slug), false);
    }

    protected function unlockKey(string $slug): string
    {
        return 'shortlink_unlocked.'.$slug;
    }
}
