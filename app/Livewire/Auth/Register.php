<?php

namespace App\Livewire\Auth;

use App\Services\SignupService;
use Livewire\Component;

class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        app(SignupService::class)->storeEmailSignup($validated);

        return redirect()->route('auth.choose-plan');
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('layouts.guest', ['title' => __('auth.register')]);
    }
}
