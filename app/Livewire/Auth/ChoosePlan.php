<?php

namespace App\Livewire\Auth;

use App\Models\Plan;
use App\Services\SignupService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ChoosePlan extends Component
{
    public bool $yearly = false;

    public function mount(): void
    {
        $signup = app(SignupService::class);

        if (Auth::check() && Auth::user()->hasSelectedPlan()) {
            $this->redirectRoute('dashboard', navigate: false);

            return;
        }

        if (! Auth::check() && ! $signup->hasPendingSignup()) {
            $this->redirectRoute('register', navigate: false);
        }
    }

    public function render()
    {
        $pending = app(SignupService::class)->getPendingSignup();

        return view('livewire.auth.choose-plan', [
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'pendingEmail' => $pending['email'] ?? Auth::user()?->email,
            'trialDays' => (int) config('qrcode.signup_trial_days', 30),
        ])->layout('layouts.guest-wide', ['title' => __('auth.choose_plan')]);
    }
}
