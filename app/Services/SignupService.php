<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SignupService
{
    public const SESSION_KEY = 'pending_signup';

    public function storeOAuthSignup(array $data): void
    {
        session([self::SESSION_KEY => [
            'type' => 'oauth',
            'name' => $data['name'],
            'email' => $data['email'],
            'google_id' => $data['google_id'],
            'avatar' => $data['avatar'] ?? null,
        ]]);
    }

    public function storeEmailSignup(array $data): void
    {
        session([self::SESSION_KEY => [
            'type' => 'email',
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]]);
    }

    public function hasPendingSignup(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public function getPendingSignup(): ?array
    {
        return session(self::SESSION_KEY);
    }

    public function clearPendingSignup(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * @return array{user: User, redirect: RedirectResponse|null}
     */
    public function completeSignup(string $planSlug, bool $yearly = false, ?User $existingUser = null): array
    {
        $user = $existingUser ?? $this->createUserFromPendingSignup();

        if ($planSlug === 'starter') {
            $this->clearPendingSignup();

            return [
                'user' => $user,
                'redirect' => redirect()->route('dashboard', ['welcome' => 1]),
            ];
        }

        $result = app(SubscriptionService::class)->subscribe(
            $user,
            $planSlug,
            $yearly,
            withTrial: true,
            checkoutUrls: [
                'success' => route('dashboard', ['welcome' => 1]),
                'cancel' => route('auth.choose-plan', ['cancelled' => 1]),
            ],
        );

        $this->clearPendingSignup();

        return [
            'user' => $user,
            'redirect' => $result instanceof RedirectResponse
                ? $result
                : redirect()->route('dashboard', ['welcome' => 1]),
        ];
    }

    public function createUserFromPendingSignup(): User
    {
        $pending = $this->getPendingSignup();

        if (! $pending) {
            throw ValidationException::withMessages([
                'plan' => __('auth.plan_selection_required'),
            ]);
        }

        $existingUser = User::where('email', $pending['email'])->first();

        if ($existingUser) {
            return $existingUser;
        }

        $attributes = [
            'name' => $pending['name'],
            'email' => $pending['email'],
        ];

        if ($pending['type'] === 'oauth') {
            $attributes['google_id'] = $pending['google_id'];
            $attributes['avatar'] = $pending['avatar'];
            $attributes['email_verified_at'] = now();
            $attributes['password'] = Hash::make(str()->random(24));
        } else {
            $attributes['password'] = Hash::make($pending['password']);
        }

        $user = User::create($attributes);

        if ($pending['type'] === 'email') {
            event(new Registered($user));
        }

        return $user;
    }
}
