<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\SubscriptionSyncService;
use Illuminate\Console\Command;

class SyncSubscriptions extends Command
{
    protected $signature = 'subscriptions:sync
                            {--chunk=100 : Number of users to process per batch}
                            {--sleep=500 : Milliseconds to wait between Stripe API calls}
                            {--user= : Sync only this user ID}';

    protected $description = 'Reconcile local subscription records with Stripe and process lapsed account deletions';

    public function handle(
        SubscriptionSyncService $syncService,
        AccountDeletionService $accountDeletionService,
    ): int {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $sleepMs = max(0, (int) $this->option('sleep'));
        $userId = $this->option('user');

        $query = User::query()
            ->with('subscriptions')
            ->orderBy('id');

        if ($userId) {
            $query->whereKey($userId);
        }

        $totals = [
            'users' => 0,
            'synced' => 0,
            'canceled' => 0,
            'deleted_subscriptions' => 0,
            'skipped' => 0,
            'errors' => 0,
            'deletion_scheduled' => 0,
            'deletion_waiting' => 0,
            'deletion_cleared' => 0,
            'accounts_deleted' => 0,
        ];

        if ($syncService->canSyncWithStripe()) {
            $this->info('Starting subscription sync with Stripe…');

            $query->clone()->chunkById($chunkSize, function ($users) use ($syncService, $sleepMs, &$totals) {
                foreach ($users as $user) {
                    $totals['users']++;

                    $liveSubscriptions = $user->subscriptions->filter(
                        fn ($subscription) => $syncService->isLiveStripeSubscription($subscription)
                    );

                    if ($liveSubscriptions->isEmpty()) {
                        continue;
                    }

                    foreach ($liveSubscriptions as $subscription) {
                        $result = $syncService->syncSubscription($subscription);

                        if ($result === 'deleted') {
                            $totals['deleted_subscriptions']++;
                        } else {
                            $totals[$result]++;
                        }

                        if ($sleepMs > 0) {
                            usleep($sleepMs * 1000);
                        }
                    }
                }
            });

            $this->newLine();
            $this->info("Stripe sync processed {$totals['users']} users.");
            $this->line("Synced: {$totals['synced']}, canceled: {$totals['canceled']}, deleted subscriptions: {$totals['deleted_subscriptions']}, skipped: {$totals['skipped']}, errors: {$totals['errors']}");
        } else {
            $this->warn('Stripe is not configured for live billing. Skipping Stripe reconciliation.');
        }

        $this->newLine();
        $this->info('Checking lapsed accounts for deletion workflow…');

        $deletionQuery = User::query()
            ->with('subscriptions')
            ->where('is_admin', false)
            ->where(function ($builder) {
                $builder->whereNotNull('account_deletion_scheduled_at')
                    ->orWhereHas('subscriptions', fn ($query) => $query->where('stripe_id', 'not like', 'dev_%'));
            })
            ->orderBy('id');

        if ($userId) {
            $deletionQuery->whereKey($userId);
        }

        $deletionQuery->chunkById($chunkSize, function ($users) use ($accountDeletionService, &$totals) {
            foreach ($users as $user) {
                $result = $accountDeletionService->processUser($user->fresh(['subscriptions']));

                match ($result) {
                    'scheduled' => $totals['deletion_scheduled']++,
                    'waiting' => $totals['deletion_waiting']++,
                    'cleared' => $totals['deletion_cleared']++,
                    'deleted' => $totals['accounts_deleted']++,
                    default => null,
                };
            }
        });

        $this->line("Deletion scheduled: {$totals['deletion_scheduled']}, waiting: {$totals['deletion_waiting']}, cleared: {$totals['deletion_cleared']}, accounts deleted: {$totals['accounts_deleted']}");

        return $totals['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
