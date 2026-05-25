<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsApiController extends Controller
{
    public function stats(Request $request, QrCode $qrCode)
    {
        if ($qrCode->user_id !== $request->user()->id) {
            abort(403);
        }

        $analytics = app(AnalyticsService::class);
        $period = $request->input('period', '30d');

        if (! $qrCode->shortLink) {
            return response()->json(['error' => 'No link data available for static QR codes.'], 404);
        }

        $linkId = $qrCode->shortLink->id;

        return response()->json([
            'totals' => $analytics->getTotalScans($linkId, $period),
            'daily' => $analytics->getDailyScans($linkId, match ($period) {
                '7d' => 7, '90d' => 90, default => 30,
            }),
            'devices' => $analytics->getDeviceBreakdown($linkId, $period),
            'browsers' => $analytics->getBrowserBreakdown($linkId, $period),
            'os' => $analytics->getOsBreakdown($linkId, $period),
            'referrers' => $analytics->getTopReferrers($linkId, 10, $period),
            'countries' => $analytics->getCountryBreakdown($linkId, $period),
        ]);
    }
}
