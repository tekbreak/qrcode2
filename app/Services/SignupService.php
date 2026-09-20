<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
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
            // Hashed before it is stored: the session store is a database table
            // and a pending signup should never leave a readable password in it.
            'password' => Hash::make($data['password']),
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
                $redirect = redirect()->route('dashboard', ['welcome' => 1]);
            } else {
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

                if ($result instanceof RedirectResponse) {
                    $this->finalizeSignup($user, $pending, $wasNewUser, markPlan: false);

                    return [
                        'user' => $user->fresh(),
                        'redirect' => $result,
                    ];
                }

                $redirect = redirect()->route('dashboard', ['welcome' => 1]);
            }

            $this->finalizeSignup($user, $pending, $wasNewUser, $planSlug);

            return [
                'user' => $user->fresh(),
                'redirect' => $redirect,
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
            // A row for this address exists that did not when the signup was
            // stored: either this same signup submitted twice, or someone else
            // claiming the address in between. Only the former may continue --
            // returning any other account hands the caller a session for it
            // without ever having authenticated against it.
            if (! $this->pendingSignupCreated($existingUser, $pending)) {
                throw new \RuntimeException(__('auth.email_already_registered'));
            }

            return $existingUser;
        }

        $attributes = [
            'name' => $pending['name'],
            'email' => $pending['email'],
        ];

        if ($pending['type'] === 'oauth') {
            $attributes['google_id'] = $pending['google_id'];
            $attributes['avatar'] = $pending['avatar'];
            $attributes['password'] = str()->random(24);

            // Google has already verified the address, so the account starts
            // out verified. 'email_verified_at' is not fillable, so it has to
            // be forced past mass assignment -- through create() it would be
            // silently dropped and the user would land on the verification
            // notice with no way off it.
            $user = new User($attributes);
            $user->forceFill(['email_verified_at' => now()])->save();

            return $user;
        }

        // Already hashed in storeEmailSignup(); forceFill past the 'hashed' cast
        // rather than relying on it to detect that.
        $user = new User($attributes);
        $user->forceFill(['password' => $pending['password']])->save();

        return $user;
    }

    /**
     * Whether $user is the row this pending signup already created, as opposed
     * to a pre-existing account that merely shares the address.
     *
     * @param  array<string, mixed>  $pending
     */
    protected function pendingSignupCreated(User $user, array $pending): bool
    {
        if (($pending['type'] ?? null) === 'oauth') {
            $googleId = $pending['google_id'] ?? null;

            return is_string($googleId)
                && is_string($user->google_id)
                && hash_equals($user->google_id, $googleId);
        }

        // The pending password was hashed once, in storeEmailSignup(), and
        // written to the row verbatim. An identical hash therefore means this
        // signup is what wrote it -- no other account could match, since bcrypt
        // salts every hash of the same password differently.
        $password = $pending['password'] ?? null;

        return is_string($password)
            && is_string($user->password)
            && hash_equals($user->password, $password);
    }

    protected function finalizeSignup(
        User $user,
        ?array $pending,
        bool $wasNewUser,
        ?string $planSlug = null,
        bool $markPlan = true,
    ): void {
        if ($markPlan && $planSlug !== null) {
            $this->markPlanSelected($user, $planSlug);
        }

        $this->clearPendingSignup();
        $this->queueVerificationEmail($user, $pending, $wasNewUser);
    }

    /**
     * @param  array<string, mixed>|null  $pending
     */
    protected function queueVerificationEmail(User $user, ?array $pending, bool $wasNewUser): void
    {
        if (! $wasNewUser || ($pending['type'] ?? null) !== 'email') {
            return;
        }

        dispatch(function () use ($user): void {
            $freshUser = $user->fresh();

            if (! $freshUser || $freshUser->hasVerifiedEmail()) {
                return;
            }

            try {
                $freshUser->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                report($e);
            }
        })->afterResponse();
    }
}
