<?php

namespace Tests\Unit\Services;

use App\Enums\CreditAction;
use App\Enums\PlanTier;
use App\Exceptions\InsufficientCreditsException;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CreditServiceTest extends TestCase
{
    use RefreshDatabase;

    private CreditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CreditService::class);
    }

    public function test_deduct_reduces_balance_and_creates_transaction(): void
    {
        $user = User::factory()->create();
        $user->createCreditBalance(PlanTier::Starter);

        $transaction = $this->service->deduct(
            $user,
            CreditAction::ApiCall,
            description: 'Test deduction'
        );

        $this->assertSame(CreditAction::ApiCall, $transaction->action);
        $this->assertSame(-CreditAction::ApiCall->cost(), $transaction->amount);
        $this->assertSame(
            50 - CreditAction::ApiCall->cost(),
            $user->fresh()->creditBalance->balance
        );
    }

    public function test_deduct_throws_when_insufficient_credits(): void
    {
        $user = User::factory()->create();
        $user->creditBalance()->create([
            'balance' => 1,
            'monthly_allowance' => 5,
            'resets_at' => now()->addMonth(),
        ]);

        $this->expectException(InsufficientCreditsException::class);

        $this->service->deduct($user, CreditAction::ApiCall);
    }

    public function test_credit_increases_balance(): void
    {
        $user = User::factory()->create();
        $user->createCreditBalance(PlanTier::Free);

        $transaction = $this->service->credit(
            $user,
            CreditAction::CreditPurchase,
            10,
            'Purchased credits'
        );

        $this->assertSame(10, $transaction->amount);
        $this->assertSame(15, $user->fresh()->creditBalance->balance);
    }

    public function test_can_afford_returns_false_when_balance_too_low(): void
    {
        $user = User::factory()->create();
        $user->creditBalance()->create([
            'balance' => 0,
            'monthly_allowance' => 5,
            'resets_at' => now()->addMonth(),
        ]);

        $this->assertFalse($this->service->canAfford($user, CreditAction::ApiCall));
    }

    public function test_reset_monthly_refills_balance(): void
    {
        $user = User::factory()->create();
        $user->creditBalance()->create([
            'balance' => 2,
            'monthly_allowance' => 50,
            'resets_at' => now()->subDay(),
        ]);

        $this->service->resetMonthly($user);

        $this->assertSame(PlanTier::Free->monthlyCredits(), $user->fresh()->creditBalance->balance);
        $this->assertSame(PlanTier::Free->monthlyCredits(), $user->fresh()->creditBalance->monthly_allowance);
        $this->assertTrue(
            CreditTransaction::where('user_id', $user->id)
                ->where('action', CreditAction::MonthlyReset)
                ->exists()
        );
    }

    public function test_deduct_for_unlimited_plan_does_not_persist_transaction(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;
        $user->shouldReceive('hasUnlimitedCredits')->andReturn(true);
        $user->shouldReceive('getAttribute')->with('creditBalance')->andReturn(null);
        $user->creditBalance = null;

        $transaction = $this->service->deduct($user, CreditAction::ApiCall);

        $this->assertNull($transaction->id);
        $this->assertSame(0, $transaction->amount);
        $this->assertDatabaseCount('credit_transactions', 0);
    }
}
