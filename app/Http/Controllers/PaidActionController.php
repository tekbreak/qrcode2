<?php

namespace App\Http\Controllers;

use App\Models\PaidAction;
use App\Services\PaidActionService;
use Illuminate\Http\Request;

class PaidActionController extends Controller
{
    public function success(Request $request, PaidAction $paidAction, PaidActionService $paidActionService)
    {
        abort_unless($paidAction->user_id === auth()->id(), 403);

        if ($paidAction->isCompleted()) {
            return redirect()->route('qr-codes.index')->with('status', __('qr.updated'));
        }

        $paidActionService->applyAction($paidAction);

        return redirect()->route('qr-codes.index')->with('status', __('qr.updated'));
    }

    public function cancel(PaidAction $paidAction)
    {
        abort_unless($paidAction->user_id === auth()->id(), 403);

        if ($paidAction->isPending()) {
            $paidAction->update(['status' => 'cancelled']);
        }

        if ($paidAction->qr_code_id) {
            return redirect()->route('qr-codes.edit', $paidAction->qr_code_id)
                ->with('error', __('qr.paid_action_cancelled'));
        }

        return redirect()->route('qr-codes.index')
            ->with('error', __('qr.paid_action_cancelled'));
    }
}
