<?php

namespace App\Http\Controllers;

use App\Jobs\RecordScanJob;
use App\Models\QrCode;
use App\Models\ShortLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RedirectController extends Controller
{
    public function handle(Request $request, string $slug)
    {
        $cacheKey = "shortlink:{$slug}";

        $linkData = Cache::remember($cacheKey, 300, function () use ($slug) {
            $link = ShortLink::with('qrCode')
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (! $link) return null;

            return [
                'id' => $link->id,
                'destination_url' => $link->destination_url,
                'qr_code_id' => $link->qr_code_id,
                'rules' => $link->rules,
                'password_hash' => $link->password_hash,
                'expires_at' => $link->expires_at?->toIso8601String(),
                'max_scans' => $link->max_scans,
                'qr_user_id' => $link->qrCode?->user_id,
            ];
        });

        if (! $linkData) {
            abort(404);
        }

        if ($linkData['expires_at'] && now()->isAfter($linkData['expires_at'])) {
            Cache::forget($cacheKey);
            abort(410, 'This link has expired.');
        }

        if ($linkData['max_scans']) {
            $totalScans = QrCode::where('id', $linkData['qr_code_id'])->value('total_scans') ?? 0;

            if ($totalScans >= $linkData['max_scans']) {
                Cache::forget($cacheKey);
                abort(410, 'This link has reached its scan limit.');
            }
        }

        if ($linkData['password_hash']) {
            $password = $request->input('password');
            if (! $password || ! password_verify($password, $linkData['password_hash'])) {
                return response()->view('redirect.password', [
                    'slug' => $slug,
                    'error' => $password ? 'Invalid password.' : null,
                ], $password ? 403 : 200);
            }
        }

        RecordScanJob::dispatch(
            shortLinkId: $linkData['id'],
            qrCodeId: $linkData['qr_code_id'],
            userId: $linkData['qr_user_id'],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            referrer: $request->header('referer'),
        );

        return redirect()->away($linkData['destination_url'], 302);
    }
}
