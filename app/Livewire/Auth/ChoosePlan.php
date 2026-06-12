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
        $signup = app(SignupService::class);

        if (Auth::check() && ! $signup->hasPendingSignup()) {
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

            return $this->redirect($result['redirect']->getTargetUrl(), navigate: $this->shouldNavigateTo($result['redirect']->getTargetUrl()));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->errorMessage = $e instanceof \RuntimeException
                ? $e->getMessage()
                : __('auth.plan_selection_failed');
        }
    }

    protected function shouldNavigateTo(string $url): bool
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $targetHost = parse_url($url, PHP_URL_HOST);

        return $appHost && $targetHost && strcasecmp($appHost, $targetHost) === 0;
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
