<?php

namespace App\Livewire\Analytics;

use App\Models\QrCode;
use App\Services\AnalyticsService;
use Livewire\Component;

class AnalyticsIndex extends Component
{
    public ?int $qrCodeId = null;
    public string $period = '30d';

    public function mount(?QrCode $qrCode = null)
    {
        if ($qrCode?->exists) {
            $this->qrCodeId = $qrCode->id;
        }
    }

    public function render()
    {
        $user = auth()->user();
        $analytics = app(AnalyticsService::class);

        $qrCodes = $user->qrCodes()
            ->where('is_dynamic', true)
            ->with('shortLink')
            ->orderByDesc('total_scans')
            ->get();

        $selectedQr = null;
        $dailyScans = collect();
        $scanTotals = ['total' => 0, 'unique' => 0];
        $devices = collect();
        $browsers = collect();
        $osStat = collect();
        $referrers = collect();
        $countries = collect();

        if ($this->qrCodeId) {
            $selectedQr = $qrCodes->firstWhere('id', $this->qrCodeId);
            if ($selectedQr?->shortLink) {
                $linkId = $selectedQr->shortLink->id;
                $days = match ($this->period) {
                    '7d' => 7,
                    '90d' => 90,
                    default => 30,
                };
                $dailyScans = $analytics->getDailyScans($linkId, $days);
                $scanTotals = $analytics->getTotalScans($linkId, $this->period);
                $devices = $analytics->getDeviceBreakdown($linkId, $this->period);
                $browsers = $analytics->getBrowserBreakdown($linkId, $this->period);
                $osStat = $analytics->getOsBreakdown($linkId, $this->period);
                $referrers = $analytics->getTopReferrers($linkId, 10, $this->period);
                $countries = $analytics->getCountryBreakdown($linkId, $this->period);
            }
        }

        return view('livewire.analytics.analytics-index', [
            'qrCodes' => $qrCodes,
            'selectedQr' => $selectedQr,
            'dailyScans' => $dailyScans,
            'scanTotals' => $scanTotals,
            'devices' => $devices,
            'browsers' => $browsers,
            'osStat' => $osStat,
            'referrers' => $referrers,
            'countries' => $countries,
        ])->layout('layouts.app', ['title' => __('nav.analytics')]);
    }
}
