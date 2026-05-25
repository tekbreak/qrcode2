<?php

namespace App\Livewire;

use App\Services\AnalyticsService;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $analytics = app(AnalyticsService::class);
        $user = auth()->user();

        return view('livewire.dashboard', [
            'stats' => $analytics->getUserStats($user->id),
            'topQrCodes' => $analytics->getTopQrCodes($user->id),
            'creditBalance' => $user->creditBalance,
        ])->layout('layouts.app', ['title' => __('nav.dashboard')]);
    }
}
