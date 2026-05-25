<?php

namespace App\Services;

use App\Enums\CreditAction;
use App\Models\CreditBalance;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditService
{
    public function deduct(User $user, CreditAction $action, ?int $amount = null, ?string $description = null, array $metadata = []): CreditTransaction
    {
        $amount ??= $action->cost();

        if ($user->hasUnlimitedCredits()) {
            return new CreditTransaction([
                'user_id' => $user->id,
                'action' => $action,
                'amount' => 0,
                'balance_after' => $user->creditBalance?->balance ?? 0,
                'description' => ($description ?? $action->label()) . ' (unlimited plan)',
                'metadata' => $metadata,
            ]);
        }

        return DB::transaction(function () use ($user, $action, $amount, $description, $metadata) {
            $balance = CreditBalance::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $balance || $balance->balance < $amount) {
                throw new \App\Exceptions\InsufficientCreditsException(
                    "Insufficient credits. Required: {$amount}, available: " . ($balance?->balance ?? 0)
                );
            }

            $balance->decrement('balance', $amount);

            return CreditTransaction::create([
                'user_id' => $user->id,
                'action' => $action,
                'amount' => -$amount,
                'balance_after' => $balance->fresh()->balance,
                'description' => $description ?? $action->label(),
                'metadata' => $metadata,
            ]);
        });
    }

    public function credit(User $user, CreditAction $action, int $amount, ?string $description = null, array $metadata = []): CreditTransaction
    {
        return DB::transaction(function () use ($user, $action, $amount, $description, $metadata) {
            $balance = CreditBalance::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $balance) {
                $balance = $user->createCreditBalance();
            }

            $balance->increment('balance', $amount);

            return CreditTransaction::create([
                'user_id' => $user->id,
                'action' => $action,
                'amount' => $amount,
                'balance_after' => $balance->fresh()->balance,
                'description' => $description ?? $action->label(),
                'metadata' => $metadata,
            ]);
        });
    }

    public function resetMonthly(User $user): void
    {
        if ($user->hasUnlimitedCredits()) {
            return;
        }

        DB::transaction(function () use ($user) {
            $balance = CreditBalance::where('user_id', $user->id)->lockForUpdate()->first();
            if (! $balance) return;

            $allowance = $user->planTier()->monthlyCredits();
            $oldBalance = $balance->balance;

            $balance->update([
                'balance' => $allowance,
                'monthly_allowance' => $allowance,
                'resets_at' => now()->addMonth()->startOfMonth(),
            ]);

            CreditTransaction::create([
                'user_id' => $user->id,
                'action' => CreditAction::MonthlyReset,
                'amount' => $allowance - $oldBalance,
                'balance_after' => $allowance,
                'description' => "Monthly credit reset to {$allowance}",
            ]);
        });
    }

    public function updateAllowance(User $user, int $newAllowance): void
    {
        $balance = $user->creditBalance;
        if ($balance) {
            $balance->update(['monthly_allowance' => $newAllowance]);
        }
    }

    public function canAfford(User $user, CreditAction $action, ?int $amount = null): bool
    {
        if ($user->hasUnlimitedCredits()) {
            return true;
        }

        $cost = $amount ?? $action->cost();
        return ($user->creditBalance?->balance ?? 0) >= $cost;
    }
}
