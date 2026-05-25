<?php

namespace App\Services;

use App\Models\QrCode;
use App\Models\Scan;
use App\Models\ShortLink;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getDailyScans(int $shortLinkId, int $days = 30): Collection
    {
        $startDate = now()->subDays($days)->startOfDay();

        $scans = Scan::where('short_link_id', $shortLinkId)
            ->where('scanned_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(scanned_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN is_unique = 1 THEN 1 ELSE 0 END) as unique_scans')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dateRange = collect();
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $found = $scans->firstWhere('date', $date);
            $dateRange->push([
                'date' => $date,
                'label' => Carbon::parse($date)->format('M d'),
                'total' => $found ? (int) $found->total : 0,
                'unique' => $found ? (int) $found->unique_scans : 0,
            ]);
        }

        return $dateRange;
    }

    public function getTotalScans(int $shortLinkId, ?string $period = null): array
    {
        $query = Scan::where('short_link_id', $shortLinkId);

        if ($period) {
            $query->where('scanned_at', '>=', match ($period) {
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
                '90d' => now()->subDays(90),
                default => now()->subDays(30),
            });
        }

        return [
            'total' => $query->count(),
            'unique' => (clone $query)->where('is_unique', true)->count(),
        ];
    }

    public function getUserStats(int $userId): array
    {
        $qrCodeIds = QrCode::where('user_id', $userId)->pluck('id');
        $shortLinkIds = ShortLink::whereIn('qr_code_id', $qrCodeIds)->pluck('id');

        $totalScans = Scan::whereIn('short_link_id', $shortLinkIds)->count();
        $todayScans = Scan::whereIn('short_link_id', $shortLinkIds)
            ->where('scanned_at', '>=', now()->startOfDay())
            ->count();

        return [
            'total_qr_codes' => $qrCodeIds->count(),
            'active_qr_codes' => QrCode::where('user_id', $userId)->where('status', 'active')->count(),
            'total_scans' => $totalScans,
            'today_scans' => $todayScans,
        ];
    }

    public function getTopQrCodes(int $userId, int $limit = 5): Collection
    {
        return QrCode::where('user_id', $userId)
            ->where('is_dynamic', true)
            ->orderByDesc('total_scans')
            ->limit($limit)
            ->get();
    }

    public function getDeviceBreakdown(int $shortLinkId, ?string $period = null): Collection
    {
        $query = Scan::where('short_link_id', $shortLinkId);
        if ($period) {
            $query->where('scanned_at', '>=', $this->periodToDate($period));
        }

        return $query->select('device_type', DB::raw('COUNT(*) as count'))
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->orderByDesc('count')
            ->get();
    }

    public function getBrowserBreakdown(int $shortLinkId, ?string $period = null): Collection
    {
        $query = Scan::where('short_link_id', $shortLinkId);
        if ($period) {
            $query->where('scanned_at', '>=', $this->periodToDate($period));
        }

        return $query->select('browser', DB::raw('COUNT(*) as count'))
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('count')
            ->get();
    }

    public function getOsBreakdown(int $shortLinkId, ?string $period = null): Collection
    {
        $query = Scan::where('short_link_id', $shortLinkId);
        if ($period) {
            $query->where('scanned_at', '>=', $this->periodToDate($period));
        }

        return $query->select('os', DB::raw('COUNT(*) as count'))
            ->whereNotNull('os')
            ->groupBy('os')
            ->orderByDesc('count')
            ->get();
    }

    public function getTopReferrers(int $shortLinkId, int $limit = 10, ?string $period = null): Collection
    {
        $query = Scan::where('short_link_id', $shortLinkId);
        if ($period) {
            $query->where('scanned_at', '>=', $this->periodToDate($period));
        }

        return $query->select('referrer', DB::raw('COUNT(*) as count'))
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    public function getCountryBreakdown(int $shortLinkId, ?string $period = null): Collection
    {
        $query = Scan::where('short_link_id', $shortLinkId);
        if ($period) {
            $query->where('scanned_at', '>=', $this->periodToDate($period));
        }

        return $query->select('country', DB::raw('COUNT(*) as count'))
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('count')
            ->get();
    }

    protected function periodToDate(string $period): Carbon
    {
        return match ($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDays(30),
        };
    }
}
