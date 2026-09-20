<?php

namespace App\Livewire\Settings;

use App\Services\AccountDeletionService;
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

    public string $delete_password = '';

    public function mount()
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->locale = $user->locale ?? 'en';
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

        // The 'hashed' cast handles hashing on assignment.
        $user->update(['password' => $this->new_password]);

        // A password change should evict anyone else holding a session or token
        // for this account - that is the whole point of changing it.
        auth()->logoutOtherDevices($this->new_password);
        $user->tokens()->delete();

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        session()->flash('status', 'Password updated successfully.');
    }

    public function deleteAccount(AccountDeletionService $deletions)
    {
        $this->validate(['delete_password' => 'required|string']);

        $user = auth()->user();

        if (! Hash::check($this->delete_password, $user->password)) {
            $this->addError('delete_password', __('auth.password_incorrect'));

            return;
        }

        if ($user->subscribed()) {
            $user->subscription()->cancel();
        }

        // Log out first. SessionGuard::logout() cycles the remember token, which
        // saves the model - and saving a deleted model re-inserts the row.
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        // Use the thorough routine: it also clears API tokens, sessions, teams
        // and the Stripe customer inside a transaction.
        $deletions->deleteUserCompletely($user);

        return redirect('/');
    }

    public function render()
    {
        return view('livewire.settings.settings-index')
            ->layout('layouts.app', ['title' => __('nav.settings')]);
    }
}
