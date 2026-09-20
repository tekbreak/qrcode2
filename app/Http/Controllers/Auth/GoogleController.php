<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SignupService;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->with('error', __('auth.failed'));
        }

        // Google will happily return an unverified address; without this check
        // anyone could claim someone else's email through the provider.
        if (! ($googleUser->user['email_verified'] ?? false)) {
            return redirect()->route('login')->with('error', __('auth.google_email_unverified'));
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Only an account already linked to this Google identity may be
            // signed in here. Linking a password account by matching addresses
            // is the classic OAuth account-takeover path, so that has to happen
            // from an authenticated session instead.
            if ($user->google_id !== null && $user->google_id !== $googleUser->getId()) {
                return redirect()->route('login')->with('error', __('auth.failed'));
            }

            if ($user->google_id === null) {
                return redirect()->route('login')->with('error', __('auth.link_google_from_settings'));
            }

            $user->update(['avatar' => $googleUser->getAvatar()]);

            Auth::login($user, remember: true);

            return redirect()->intended(route('dashboard'));
        }

        app(SignupService::class)->storeOAuthSignup([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
        ]);

        return redirect()->route('auth.choose-plan');
    }
}
