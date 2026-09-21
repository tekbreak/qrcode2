<?php

namespace App\Livewire\Admin;

use App\Services\AdminMetricsService;
use Livewire\Attributes\Url;
use Livewire\Component;

class AdminUsage extends Component
{
    /** Days covered by the scan breakdowns. */
    #[Url(except: '30')]
    public string $period = '30';

    public function render()
    {
        $metrics = app(AdminMetricsService::class);
        $days = in_array($this->period, ['7', '30', '90'], true) ? (int) $this->period : 30;

        return view('livewire.admin.admin-usage', [
            'stats' => $metrics->usageStats(),
            'qrCreated' => $metrics->dailyQrCodes(30),
            'scans' => $metrics->dailyScans(30),
            'byType' => $metrics->qrCodesByType(),
            'byStatus' => $metrics->qrCodesByStatus(),
            'countries' => $metrics->scanBreakdown('country', $days),
            'devices' => $metrics->scanBreakdown('device_type', $days),
            'browsers' => $metrics->scanBreakdown('browser', $days),
            'operatingSystems' => $metrics->scanBreakdown('os', $days),
            'topQrCodes' => $metrics->topQrCodes(10),
            'days' => $days,
        ])->layout('layouts.app', ['title' => __('admin.usage')]);
    }
}
