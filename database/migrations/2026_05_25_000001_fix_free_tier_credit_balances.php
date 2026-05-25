<?php

use App\Enums\PlanTier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $freeAllowance = PlanTier::Free->monthlyCredits();

        $freeUserIds = DB::table('users')
            ->whereNotIn('id', function ($query) {
                $query->select('user_id')
                    ->from('subscriptions')
                    ->where('stripe_status', 'active');
            })
            ->pluck('id');

        if ($freeUserIds->isNotEmpty()) {
            DB::table('credit_balances')
                ->whereIn('user_id', $freeUserIds)
                ->where('monthly_allowance', '!=', $freeAllowance)
                ->update([
                    'monthly_allowance' => $freeAllowance,
                    'balance' => $freeAllowance,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        //
    }
};
