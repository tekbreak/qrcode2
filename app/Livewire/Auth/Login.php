<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Database\Seeders\MockUserSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 60;

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if (RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($this->throttleKey());

            $this->addError('email', __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]));

            return;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

            $this->addError('email', __('auth.failed'));

            return;
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Keyed on address and IP together: on the address alone an attacker could
     * lock a victim out, on the IP alone they could just rotate proxies.
     */
    protected function throttleKey(): string
    {
        return 'login|'.Str::transliterate(Str::lower($this->email)).'|'.request()->ip();
    }

    public function quickLogin(string $email)
    {
        // 404 rather than 403: outside development this endpoint should not
        // advertise that it exists.
        abort_unless(app()->environment('local', 'testing'), 404);
        abort_unless(config('app.dev_quick_login'), 404);

        if (! in_array($email, MockUserSeeder::emails(), true)) {
            abort(404);
        }

        $user = User::where('email', $email)->firstOrFail();

        Auth::login($user, remember: true);
        session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login', [
            'mockAccounts' => config('app.dev_quick_login') ? MockUserSeeder::accounts() : [],
        ])->layout('layouts.guest', ['title' => __('auth.login')]);
    }
}
