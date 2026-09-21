<?php

namespace App\Livewire\Admin\Concerns;

use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\ImpersonationService;
use App\Services\SubscriptionService;

/**
 * Account actions shared by the admin user list and the user detail page.
 *
 * Every action re-checks the admin flag: route middleware already guards the
 * page, but these are state-changing calls and should not depend on it alone.
 */
trait ManagesUsers
{
    public function toggleAdmin(int $userId): void
    {
        $user = $this->authorizeTarget($userId);

        if (! $user) {
            return;
        }

        $user->update(['is_admin' => ! $user->is_admin]);

        session()->flash('status', $user->is_admin
            ? __('admin.admin_granted', ['name' => $user->name])
            : __('admin.admin_revoked', ['name' => $user->name]));
    }

    public function deleteUser(int $userId)
    {
        $user = $this->authorizeTarget($userId);

        if (! $user) {
            return null;
        }

        $email = $user->email;

        app(AccountDeletionService::class)->deleteUserCompletely($user);

        session()->flash('status', __('admin.user_deleted', ['email' => $email]));

        return $this->afterUserDeleted();
    }

    public function impersonate(int $userId)
    {
        $this->assertAdmin();

        $user = User::findOrFail($userId);

        try {
            app(ImpersonationService::class)->start(auth()->user(), $user);
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return null;
        }

        session()->flash('status', __('admin.impersonation_started', ['name' => $user->name]));

        return $this->redirect(route('dashboard'), navigate: false);
    }

    public function changePlan(int $userId, string $planSlug, bool $yearly = false): void
    {
        $this->assertAdmin();

        $user = User::findOrFail($userId);

        try {
            app(SubscriptionService::class)->adminChangePlan($user, $planSlug, $yearly);

            session()->flash('status', __('admin.plan_changed', [
                'plan' => $planSlug === 'starter'
                    ? __('admin.status_free')
                    : ucfirst($planSlug),
            ]));
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', __('admin.plan_change_failed', ['error' => $e->getMessage()]));
        }
    }

    public function cancelSubscription(int $userId): void
    {
        $this->assertAdmin();

        $user = User::findOrFail($userId);

        try {
            app(SubscriptionService::class)->adminCancelSubscription($user);
            session()->flash('status', __('admin.subscription_canceled'));
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', __('admin.cancel_failed', ['error' => $e->getMessage()]));
        }
    }

    /** What to do once a user has been removed — overridden by the detail page. */
    protected function afterUserDeleted()
    {
        return null;
    }

    protected function assertAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    /** Resolve the target user, refusing actions an admin aims at themselves. */
    protected function authorizeTarget(int $userId): ?User
    {
        $this->assertAdmin();

        $user = User::findOrFail($userId);

        if ($user->is(auth()->user())) {
            session()->flash('error', __('admin.self_action_blocked'));

            return null;
        }

        return $user;
    }
}
