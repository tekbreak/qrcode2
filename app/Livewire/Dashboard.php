<?php

namespace App\Livewire;

use App\Services\AnalyticsService;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): void
    {
        $user = auth()->user();

        if (request()->boolean('welcome') && $user->subscribed() && ! $user->plan_selected_at) {
            $user->forceFill([
                'selected_plan' => $user->planTier()->value,
                'plan_selected_at' => now(),
            ])->save();
        }
    }

    public function render()
    {
        $analytics = app(AnalyticsService::class);
        $user = auth()->user();

        return view('livewire.dashboard', [
            'stats' => $analytics->getUserStats($user->id),
            'topQrCodes' => $analytics->getTopQrCodes($user->id),
        ])->layout('layouts.app', ['title' => __('nav.dashboard')]);
    }
}
