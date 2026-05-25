<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MagicLinkMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class MagicLinkController extends Controller
{
    public function send(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return back()->with('status', __('auth.magic_link_sent'));
        }

        $url = URL::temporarySignedRoute(
            'auth.magic-link.verify',
            now()->addMinutes(15),
            ['user' => $user->id]
        );

        Mail::to($user)->send(new MagicLinkMail($url));

        return back()->with('status', __('auth.magic_link_sent'));
    }

    public function verify(Request $request, User $user)
    {
        if (! $request->hasValidSignature()) {
            abort(401, __('auth.magic_link_invalid'));
        }

        if (! $user->email_verified_at) {
            $user->markEmailAsVerified();
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }
}
