<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class VerifyEmail extends Component
{
    public bool $linkSent = false;

    public function mount()
    {
        if (auth()->user()?->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }
    }

    public function resend(): void
    {
        $key = 'verification-resend|'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            $this->addError('email', __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]));

            return;
        }

        RateLimiter::hit($key, 600);

        auth()->user()->sendEmailVerificationNotification();

        $this->linkSent = true;
    }

    public function render()
    {
        return view('livewire.auth.verify-email')
            ->layout('layouts.guest', ['title' => __('auth.verify_email')]);
    }
}
