<?php

namespace App\Console\Commands;

use App\Providers\AppServiceProvider;
use Illuminate\Console\Command;

class CheckBillingConfig extends Command
{
    protected $signature = 'billing:check';

    protected $description = 'Report Stripe configuration gaps that would disable billing features';

    public function handle(): int
    {
        $problems = AppServiceProvider::billingConfigProblems();

        if ($problems === []) {
            $this->info('Billing configuration looks complete.');

            return self::SUCCESS;
        }

        foreach ($problems as $problem) {
            $this->warn('• '.$problem);
        }

        $this->newLine();
        $this->line('None of these expose the application: plan grants, paid actions and the');
        $this->line('Stripe webhook all fail closed on their own. They disable billing features.');

        return self::FAILURE;
    }
}
