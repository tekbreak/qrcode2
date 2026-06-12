<?php

namespace App\Livewire\Auth;

use App\Models\Plan;
use App\Services\SignupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ChoosePlan extends Component
{
    public bool $yearly = false;

    public string $errorMessage = '';

    public function mount(): void
    {
        $signup = app(SignupService::class);

        if (Auth::check() && Auth::user()->hasSelectedPlan()) {
            $this->redirectRoute('dashboard', navigate: true);

            return;
        }

        if (! Auth::check() && ! $signup->hasPendingSignup()) {
            $this->redirectRoute('register', navigate: true);
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

            return $this->redirectAfterSignup($result['redirect']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->errorMessage = $this->formatSignupError($e);
        }
    }

    protected function redirectAfterSignup(RedirectResponse $redirect)
    {
        $url = $redirect->getTargetUrl();
        $dashboardPath = parse_url(route('dashboard', ['welcome' => 1]), PHP_URL_PATH) ?: '/dashboard';

        if (parse_url($url, PHP_URL_PATH) === $dashboardPath) {
            return $this->redirectRoute('dashboard', ['welcome' => 1], navigate: true);
        }

        return $this->redirect($url, navigate: false);
    }

    protected function formatSignupError(\Throwable $e): string
    {
        if ($e instanceof \RuntimeException) {
            return $e->getMessage();
        }

        if (config('app.debug')) {
            return $e->getMessage();
        }

        return __('auth.plan_selection_failed');
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
