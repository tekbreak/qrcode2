<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SubscriptionSyncService;
use Illuminate\Console\Command;

class SyncSubscriptions extends Command
{
    protected $signature = 'subscriptions:sync
                            {--chunk=100 : Number of users to process per batch}
                            {--sleep=500 : Milliseconds to wait between Stripe API calls}
                            {--user= : Sync only this user ID}';

    protected $description = 'Reconcile local subscription records with Stripe (slow, throttled daily job)';

    public function handle(SubscriptionSyncService $syncService): int
    {
        if (! $syncService->canSyncWithStripe()) {
            $this->warn('Stripe is not configured for live billing. Skipping subscription sync.');

            return self::SUCCESS;
        }

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
            'deleted' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $this->info('Starting subscription sync with Stripe…');

        $query->chunkById($chunkSize, function ($users) use ($syncService, $sleepMs, &$totals) {
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
                    $totals[$result]++;

                    if ($sleepMs > 0) {
                        usleep($sleepMs * 1000);
                    }
                }
            }
        });

        $this->newLine();
        $this->info("Processed {$totals['users']} users.");
        $this->line("Synced: {$totals['synced']}, canceled: {$totals['canceled']}, deleted: {$totals['deleted']}, skipped: {$totals['skipped']}, errors: {$totals['errors']}");

        return $totals['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
