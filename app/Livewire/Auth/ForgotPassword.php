<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class ForgotPassword extends Component
{
    public string $email = '';

    public bool $linkSent = false;

    public function sendResetLink()
    {
        $this->validate(['email' => 'required|email']);

        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey());

            $this->addError('email', __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]));

            return;
        }

        RateLimiter::hit($this->throttleKey(), 600);

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->linkSent = true;
            $this->reset('email');
        } else {
            $this->addError('email', __($status));
        }
    }

    protected function throttleKey(): string
    {
        return 'password-reset|'.Str::transliterate(Str::lower($this->email)).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')
            ->layout('layouts.guest', ['title' => __('auth.forgot_password')]);
    }
}
