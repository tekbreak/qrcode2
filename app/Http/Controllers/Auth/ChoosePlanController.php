<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SignupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ChoosePlanController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => 'required|string|in:starter,pro,enterprise',
            'yearly' => 'sometimes|boolean',
        ]);

        try {
            $result = app(SignupService::class)->completeSignup(
                $validated['plan'],
                $request->boolean('yearly'),
                Auth::user(),
            );

            if (! Auth::check()) {
                Auth::login($result['user'], remember: true);
            }

            return $result['redirect'];
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            $message = $e instanceof \RuntimeException
                ? $e->getMessage()
                : __('auth.plan_selection_failed');

            return redirect()
                ->route('auth.choose-plan')
                ->withInput()
                ->with('error', $message);
        }
    }
}
