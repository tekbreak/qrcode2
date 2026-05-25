<?php

namespace App\Http\Controllers;

use App\Enums\CreditAction;
use App\Services\CreditService;
use Illuminate\Http\Request;

class CreditPurchaseController extends Controller
{
    public function success(Request $request, CreditService $creditService)
    {
        $user = $request->user();
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()->route('billing.index')->with('error', 'Invalid purchase session.');
        }

        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect()->route('billing.index')->with('error', 'Payment not completed.');
            }

            $packSlug = $session->metadata->credit_pack ?? null;
            $credits = (int) ($session->metadata->credits ?? 0);
            $userId = (int) ($session->metadata->user_id ?? 0);

            if ($userId !== $user->id || $credits <= 0) {
                return redirect()->route('billing.index')->with('error', 'Invalid purchase data.');
            }

            $existing = $user->creditTransactions()
                ->where('metadata->stripe_session_id', $sessionId)
                ->exists();

            if ($existing) {
                return redirect()->route('billing.index')->with('success', 'Credits already added.');
            }

            $creditService->credit(
                $user,
                CreditAction::CreditPurchase,
                $credits,
                "Purchased {$credits} credits ({$packSlug})",
                ['stripe_session_id' => $sessionId, 'pack' => $packSlug]
            );

            return redirect()->route('billing.index')->with('success', "Successfully added {$credits} credits to your account!");
        } catch (\Exception $e) {
            return redirect()->route('billing.index')->with('error', 'Could not verify purchase. Please contact support.');
        }
    }
}
