<div>
    <div class="text-center">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('auth.choose_plan') }}</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('auth.choose_plan_subtitle') }}</p>
        @if($pendingEmail)
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $pendingEmail }}</p>
        @endif
    </div>

    <div class="mt-4 flex justify-center">
        <span class="inline-flex items-center gap-2 rounded-full bg-green-100 px-4 py-1.5 text-sm font-medium text-green-800 dark:bg-green-950 dark:text-green-400">
            <i class="fa-solid fa-gift text-xs"></i>
            {{ __('auth.trial_badge', ['days' => $trialDays]) }}
        </span>
    </div>

    @if(request()->boolean('cancelled'))
        <div class="mt-4 rounded-lg bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">
            {{ __('auth.checkout_cancelled') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-950/50 dark:text-red-400">{{ session('error') }}</div>
    @endif

    <div class="mt-8 flex items-center justify-center gap-3">
        <span class="text-sm font-medium {{ !$yearly ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' }}">{{ __('landing.pricing.monthly') }}</span>
        <button wire:click="$toggle('yearly')" type="button" role="switch"
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $yearly ? 'bg-primary-600' : 'bg-gray-200 dark:bg-zinc-700' }}"
                aria-checked="{{ $yearly ? 'true' : 'false' }}">
            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 {{ $yearly ? 'translate-x-5' : 'translate-x-0' }}"></span>
        </button>
        <span class="text-sm font-medium {{ $yearly ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' }}">
            {{ __('landing.pricing.yearly') }}
            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-950 dark:text-green-400">{{ __('landing.pricing.yearly_badge') }}</span>
        </span>
    </div>

    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($plans as $plan)
            @php
                $price = $yearly && $plan->price_yearly > 0 ? $plan->price_yearly : $plan->price_monthly;
                $monthlyDisplay = $yearly && $plan->price_yearly > 0 ? $plan->price_yearly / 12 : $plan->price_monthly;
                $isPopular = $plan->slug === 'pro';
            @endphp
            <div class="relative flex flex-col rounded-xl bg-white p-6 shadow-sm ring-1 dark:bg-zinc-900 {{ $isPopular ? 'ring-primary-600 ring-2' : 'ring-gray-900/5 dark:ring-zinc-800' }}">
                @if($isPopular)
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary-600 px-3 py-1 text-xs font-semibold text-white">{{ __('landing.pricing.most_popular') }}</div>
                @endif

                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $plan->name }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $plan->description }}</p>

                <div class="mt-4">
                    @if($price === 0)
                        <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ __('landing.pricing.free') }}</span>
                    @else
                        <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">€{{ number_format($monthlyDisplay / 100, 0) }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('landing.pricing.per_month') }}</span>
                        <p class="mt-1 text-xs text-green-600 dark:text-green-400">{{ __('auth.trial_then_price', ['days' => $trialDays]) }}</p>
                        @if($yearly && $plan->price_yearly > 0)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('landing.pricing.yearly_savings', ['monthly' => number_format($plan->price_yearly / 1200, 0), 'savings' => ($plan->price_monthly * 12 - $plan->price_yearly) / 100]) }}</p>
                        @endif
                    @endif
                </div>

                <div class="mt-6 space-y-2">
                    <p class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <i class="fa-solid fa-qrcode w-4 text-center text-xs text-gray-400"></i>
                        {{ $plan->max_static_qr ? __('landing.pricing.static_limit', ['count' => $plan->max_static_qr]) : __('landing.pricing.static_unlimited') }}
                    </p>
                    <p class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <i class="fa-solid fa-arrows-rotate w-4 text-center text-xs text-emerald-500"></i>
                        @if($plan->max_dynamic_qr === null)
                            {{ __('landing.pricing.dynamic_unlimited') }}
                        @else
                            {{ __('landing.pricing.dynamic_limit', ['count' => $plan->max_dynamic_qr]) }}
                        @endif
                    </p>
                </div>

                <ul class="mt-4 flex-1 space-y-2 border-t pt-4">
                    @foreach($plan->tier()->featureSummary() as $featureLabel)
                        <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <svg class="h-4 w-4 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $featureLabel }}
                        </li>
                    @endforeach
                </ul>

                <div class="mt-6">
                    <form method="POST" action="{{ route('auth.choose-plan.store') }}">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $plan->slug }}">
                        <input type="hidden" name="yearly" value="{{ $yearly ? '1' : '0' }}">
                        <button type="submit"
                                class="w-full rounded-lg px-4 py-2.5 text-sm font-semibold transition {{ $isPopular ? 'bg-primary-600 text-white hover:bg-primary-700' : 'border border-primary-600 text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-950/50' }}">
                            {{ __('auth.select_plan', ['plan' => $plan->name]) }}
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>
