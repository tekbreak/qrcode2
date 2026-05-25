<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class SettingsIndex extends Component
{
    public string $name = '';
    public string $email = '';
    public string $locale = 'en';

    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    public function mount()
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->locale = $user->locale;
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'locale' => 'required|in:en,es',
        ]);

        auth()->user()->update([
            'name' => $this->name,
            'locale' => $this->locale,
        ]);

        session()->flash('status', 'Profile updated successfully.');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = auth()->user();

        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'The current password is incorrect.');
            return;
        }

        $user->update(['password' => Hash::make($this->new_password)]);
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        session()->flash('status', 'Password updated successfully.');
    }

    public function deleteAccount()
    {
        $user = auth()->user();

        if ($user->subscribed()) {
            $user->subscription()->cancel();
        }

        $user->qrCodes()->delete();
        $user->creditBalance?->delete();
        $user->creditTransactions()->delete();
        $user->delete();

        auth()->logout();
        session()->invalidate();

        return redirect('/');
    }

    public function render()
    {
        return view('livewire.settings.settings-index')
            ->layout('layouts.app', ['title' => __('nav.settings')]);
    }
}
