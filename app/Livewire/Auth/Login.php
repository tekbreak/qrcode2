<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Database\Seeders\MockUserSeeder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', __('auth.failed'));

            return;
        }

        session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function quickLogin(string $email)
    {
        if (! app()->environment('local')) {
            abort(403);
        }

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
            'mockAccounts' => app()->environment('local') ? MockUserSeeder::accounts() : [],
        ])->layout('layouts.guest', ['title' => __('auth.login')]);
    }
}
