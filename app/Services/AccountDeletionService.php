<?php

namespace App\Services;

use App\Mail\AccountDeletionWarningMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;

class AccountDeletionService
{
    public function graceDays(): int
    {
        return (int) config('qrcode.account_deletion_grace_days', 7);
    }

    public function hasValidSubscription(User $user): bool
    {
        return $user->subscribed('default');
    }

    public function hadLivePaidSubscription(User $user): bool
    {
        return $user->subscriptions()
            ->where('stripe_id', 'not like', 'dev_%')
            ->exists();
    }

    /**
     * @return 'cleared'|'scheduled'|'deleted'|'waiting'|'skipped'
     */
    public function processUser(User $user): string
    {
        if ($user->is_admin) {
            return 'skipped';
        }

        if ($this->hasValidSubscription($user)) {
            if ($user->account_deletion_scheduled_at !== null) {
                $this->clearScheduledDeletion($user);

                return 'cleared';
            }

            return 'skipped';
        }

        if (! $this->hadLivePaidSubscription($user)) {
            return 'skipped';
        }

        if ($user->account_deletion_scheduled_at === null) {
            $this->scheduleDeletion($user);

            return 'scheduled';
        }

        if ($this->isDeletionDue($user)) {
            $this->deleteUserCompletely($user);

            return 'deleted';
        }

        return 'waiting';
    }

    public function isDeletionDue(User $user): bool
    {
        if ($user->account_deletion_scheduled_at === null) {
            return false;
        }

        return $user->account_deletion_scheduled_at
            ->copy()
            ->addDays($this->graceDays())
            ->lte(now());
    }

    public function deletionDueAt(User $user): ?\Carbon\CarbonInterface
    {
        if ($user->account_deletion_scheduled_at === null) {
            return null;
        }

        return $user->account_deletion_scheduled_at->copy()->addDays($this->graceDays());
    }

    public function scheduleDeletion(User $user): void
    {
        $scheduledAt = now();

        $user->forceFill([
            'account_deletion_scheduled_at' => $scheduledAt,
        ])->save();

        Mail::to($user)->send(new AccountDeletionWarningMail(
            user: $user->fresh(),
            deletionDate: $scheduledAt->copy()->addDays($this->graceDays()),
        ));
    }

    public function clearScheduledDeletion(User $user): void
    {
        $user->forceFill([
            'account_deletion_scheduled_at' => null,
        ])->save();
    }

    public function deleteUserCompletely(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->qrCodes()->withTrashed()->each(function ($qrCode) {
                $qrCode->design()->delete();
                $qrCode->shortLinks()->delete();
                $qrCode->forceDelete();
            });

            $user->ownedTeams()->each(function ($team) {
                $team->customDomains()->delete();
                $team->qrCodes()->update(['team_id' => null]);
                $team->users()->detach();
                $team->delete();
            });

            $user->teams()->detach();

            $user->subscriptions()->each(function (Subscription $subscription) {
                $subscription->items()->delete();
                $subscription->delete();
            });

            $user->tokens()->delete();

            DB::table('sessions')->where('user_id', $user->id)->delete();

            if (filled($user->stripe_id) && Str::startsWith($user->stripe_id, 'cus_')) {
                try {
                    $user->stripe()->customers->delete($user->stripe_id);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $user->delete();
        });
    }
}
