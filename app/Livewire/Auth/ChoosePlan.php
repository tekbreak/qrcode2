<?php

namespace App\Livewire\Auth;

use App\Models\Plan;
use App\Services\SignupService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ChoosePlan extends Component
{
    public bool $yearly = false;

    public string $errorMessage = '';

    public function mount(): void
    {
        if (Auth::check() && Auth::user()->subscribed()) {
            $this->redirect(route('dashboard'), navigate: true);
        }

        if (! Auth::check() && ! app(SignupService::class)->hasPendingSignup()) {
            $this->redirect(route('register'), navigate: true);
        }
    }

    public function selectPlan(string $planSlug)
    {
        $this->reset('errorMessage');

        try {
            $result = app(SignupService::class)->completeSignup(
                $planSlug,
                $this->yearly,
                Auth::user(),
            );

            if (! Auth::check()) {
                Auth::login($result['user'], remember: true);
            }

            session()->regenerate();

            if ($result['redirect']) {
                return $this->redirect($result['redirect']->getTargetUrl(), navigate: false);
            }

            return redirect()->route('dashboard');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->errorMessage = $e instanceof \RuntimeException
                ? $e->getMessage()
                : __('auth.plan_selection_failed');
        }
    }

    public function render()
    {
        $pending = app(SignupService::class)->getPendingSignup();

        return view('livewire.auth.choose-plan', [
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'pendingEmail' => $pending['email'] ?? Auth::user()?->email,
            'trialDays' => (int) config('qrcode.signup_trial_days', 30),
        ])->layout('layouts.guest-wide', ['title' => __('auth.choose_plan')]);
    }
}
