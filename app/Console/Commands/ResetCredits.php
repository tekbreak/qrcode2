<?php

namespace App\Console\Commands;

use App\Models\CreditBalance;
use App\Services\CreditService;
use Illuminate\Console\Command;

class ResetCredits extends Command
{
    protected $signature = 'credits:reset';
    protected $description = 'Reset monthly credits for all users whose reset date has passed';

    public function handle(CreditService $creditService): int
    {
        $balances = CreditBalance::where('resets_at', '<=', now())
            ->with('user')
            ->cursor();

        $count = 0;

        foreach ($balances as $balance) {
            $creditService->resetMonthly($balance->user);
            $count++;
        }

        $this->info("Reset credits for {$count} users.");

        return self::SUCCESS;
    }
}
