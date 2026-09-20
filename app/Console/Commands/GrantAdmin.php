<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantAdmin extends Command
{
    protected $signature = 'user:admin {email} {--revoke : Remove admin access instead of granting it}';

    protected $description = 'Grant or revoke admin access for a user by email address';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $revoke = (bool) $this->option('revoke');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("No user found with the email address {$email}.");

            return self::FAILURE;
        }

        if ($user->is_admin === ! $revoke) {
            $this->info($revoke
                ? "{$email} is not an admin."
                : "{$email} is already an admin.");

            return self::SUCCESS;
        }

        $user->forceFill(['is_admin' => ! $revoke])->save();

        $this->info($revoke
            ? "Revoked admin access for {$email}."
            : "Granted admin access to {$email}.");

        // Both gates sit in front of /admin, and neither is implied by the
        // admin flag, so say which one would still send the user elsewhere.
        if (! $revoke && ! $user->hasSelectedPlan()) {
            $this->warn('This account has no plan selected, so /admin still redirects to the plan selector.');
        }

        if (! $revoke && ! $user->hasVerifiedEmail()) {
            $this->warn('This account is unverified. /admin itself is reachable, but the dashboard and other verified-only pages are not.');
        }

        return self::SUCCESS;
    }
}
