<?php

namespace App\Livewire\Admin;

use App\Models\PaidAction;
use App\Models\User;
use App\Services\AdminMetricsService;
use Livewire\Component;

class AdminOverview extends Component
{
    public function render()
    {
        $metrics = app(AdminMetricsService::class);

        $recentUsers = User::with('subscriptions')
            ->withCount([
                'qrCodes as qr_total_count',
            ])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (User $user) => [
                'user' => $user,
                'tier' => $metrics->tierForLoadedUser($user),
                'qr_codes' => $user->qr_total_count,
            ]);

        return view('livewire.admin.admin-overview', [
            'stats' => $metrics->overviewStats(),
            'signups' => $metrics->dailySignups(30),
            'scans' => $metrics->dailyScans(30),
            'planDistribution' => $metrics->planDistribution(),
            'attention' => $metrics->attentionItems(),
            'recentUsers' => $recentUsers,
            'recentPayments' => PaidAction::with('user:id,name,email')
                ->where('status', 'completed')
                ->orderByDesc('paid_at')
                ->limit(6)
                ->get(),
        ])->layout('layouts.app', ['title' => __('admin.title')]);
    }
}
