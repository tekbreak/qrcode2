<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Lets an admin work inside a user's session to reproduce a support issue.
 *
 * The admin's own id is parked in the session, never in the URL, and the
 * session id is rotated on both legs so an impersonation cannot be replayed.
 */
class ImpersonationService
{
    public const SESSION_KEY = 'impersonator_id';

    public function isImpersonating(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public function impersonator(): ?User
    {
        $id = session(self::SESSION_KEY);

        return $id ? User::find($id) : null;
    }

    public function start(User $admin, User $target): void
    {
        if (! $admin->is_admin) {
            abort(403);
        }

        if ($admin->is($target)) {
            throw new \RuntimeException(__('admin.impersonation_self'));
        }

        if ($this->isImpersonating()) {
            // Never nest: come back to the real admin first.
            $this->stop();
            $admin = Auth::user();
        }

        Log::warning('admin.impersonation.started', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'target_id' => $target->id,
            'target_email' => $target->email,
            'ip' => request()->ip(),
        ]);

        Auth::login($target);
        session()->put(self::SESSION_KEY, $admin->id);
        session()->regenerate();
    }

    public function stop(): ?User
    {
        $admin = $this->impersonator();

        if (! $admin) {
            return null;
        }

        Log::info('admin.impersonation.ended', [
            'admin_id' => $admin->id,
            'target_id' => Auth::id(),
            'ip' => request()->ip(),
        ]);

        session()->forget(self::SESSION_KEY);
        Auth::login($admin);
        session()->regenerate();

        return $admin;
    }
}
