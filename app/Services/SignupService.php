<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

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
     * @return array{user: User, redirect: RedirectResponse}
     */
    public function completeSignup(string $planSlug, bool $yearly = false, ?User $existingUser = null): array
    {
        $pending = $this->getPendingSignup();
        $wasNewUser = false;

        if ($existingUser) {
            $user = $existingUser;
        } else {
            $user = $this->createUserFromPendingSignup();
            $wasNewUser = true;
        }

        try {
            if ($planSlug === 'starter') {
                $this->markPlanSelected($user, $planSlug);
                $this->clearPendingSignup();

                $this->finishNewEmailSignup($user, $pending, $wasNewUser);

                return [
                    'user' => $user->fresh(),
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

            if ($result instanceof RedirectResponse) {
                $this->finishNewEmailSignup($user, $pending, $wasNewUser);

                return [
                    'user' => $user->fresh(),
                    'redirect' => $result,
                ];
            }

            $this->markPlanSelected($user, $planSlug);
            $this->finishNewEmailSignup($user, $pending, $wasNewUser);

            return [
                'user' => $user->fresh(),
                'redirect' => redirect()->route('dashboard', ['welcome' => 1]),
            ];
        } catch (ApiErrorException $e) {
            throw new \RuntimeException(__('auth.plan_payment_failed'), previous: $e);
        }
    }

    public function markPlanSelected(User $user, string $planSlug): void
    {
        $user->forceFill([
            'selected_plan' => $planSlug,
            'plan_selected_at' => now(),
        ])->save();
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
            $attributes['password'] = str()->random(24);
        } else {
            $attributes['password'] = $pending['password'];
        }

        return User::create($attributes);
    }

    /**
     * @param  array<string, mixed>|null  $pending
     */
    protected function finishNewEmailSignup(User $user, ?array $pending, bool $wasNewUser): void
    {
        if (! $wasNewUser || ($pending['type'] ?? null) !== 'email') {
            return;
        }

        dispatch(function () use ($user): void {
            if ($user->hasVerifiedEmail()) {
                return;
            }

            $user->sendEmailVerificationNotification();
        })->afterResponse();

        event(new Registered($user));
    }
}
