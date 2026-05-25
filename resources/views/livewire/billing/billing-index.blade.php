<div>
    <h1 class="text-2xl font-bold text-gray-900">{{ __('nav.billing') }}</h1>

    @if($successMessage)
        <div class="mt-4 rounded-lg bg-green-50 p-4 text-sm text-green-700">
            {{ $successMessage }}
        </div>
    @elseif(session('success') || request('success'))
        <div class="mt-4 rounded-lg bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') ?: 'Subscription updated successfully!' }}
        </div>
    @endif

    @if($errorMessage)
        <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ $errorMessage }}</div>
    @elseif(session('error'))
        <div class="mt-4 rounded-lg bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Current plan --}}
    <div class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Current Plan</p>
                <p class="text-xl font-bold text-gray-900">{{ $currentTier->label() }}</p>
                <p class="mt-1 text-sm text-gray-500">
                    <i class="fa-solid fa-coins text-amber-500 mr-0.5"></i>
                    @if($currentTier->hasUnlimitedCredits())
                        Unlimited credits
                    @else
                        {{ number_format(auth()->user()->creditBalance?->balance ?? 0) }} credits remaining
                    @endif
                </p>
            </div>
            @if($isSubscribed)
                <button wire:click="manageBilling" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Manage Billing
                </button>
            @endif
        </div>
    </div>

    {{-- Credit packs --}}
    @unless($currentTier->hasUnlimitedCredits())
    <div class="mt-8">
        <div class="flex items-center gap-3 mb-4">
            <h2 class="text-lg font-bold text-gray-900">Need more credits?</h2>
            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">One-time purchase</span>
        </div>
        <p class="text-sm text-gray-500 mb-5">
            Buy extra credits any time to maintain more dynamic QR codes, even with an active subscription.
            Purchased credits never expire and stack on top of your monthly allowance.
        </p>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($creditPacks as $pack)
                <div class="relative rounded-xl bg-white p-5 shadow-sm ring-1 {{ ($pack['popular'] ?? false) ? 'ring-amber-400 ring-2' : 'ring-gray-900/5' }} flex flex-col">
                    @if($pack['popular'] ?? false)
                        <div class="absolute -top-2.5 left-1/2 -translate-x-1/2 rounded-full bg-amber-500 px-2.5 py-0.5 text-xs font-semibold text-white">Best Value</div>
                    @endif
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-coins text-amber-500"></i>
                        <span class="text-lg font-bold text-gray-900">{{ $pack['label'] }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ $pack['description'] }}</p>
                    <div class="mt-3">
                        <span class="text-2xl font-bold text-gray-900">€{{ number_format($pack['price'] / 100, 0) }}</span>
                        <span class="text-xs text-gray-400 ml-1">€{{ number_format($pack['price'] / 100 / $pack['credits'], 2) }}/credit</span>
                    </div>
                    <button wire:click="purchaseCredits('{{ $pack['slug'] }}')"
                            wire:loading.attr="disabled"
                            class="mt-4 w-full rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600 transition disabled:opacity-50">
                        <span wire:loading.remove wire:target="purchaseCredits('{{ $pack['slug'] }}')">Buy Now</span>
                        <span wire:loading wire:target="purchaseCredits('{{ $pack['slug'] }}')">Processing…</span>
                    </button>
                </div>
            @endforeach
        </div>
    </div>
    @endunless

    {{-- Billing toggle --}}
    <div class="mt-8 flex items-center justify-center gap-3">
        <span class="text-sm font-medium {{ !$yearly ? 'text-gray-900' : 'text-gray-500' }}">Monthly</span>
        <button wire:click="$toggle('yearly')" type="button" role="switch"
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $yearly ? 'bg-primary-600' : 'bg-gray-200' }}"
                aria-checked="{{ $yearly ? 'true' : 'false' }}">
            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 {{ $yearly ? 'translate-x-5' : 'translate-x-0' }}"></span>
        </button>
        <span class="text-sm font-medium {{ $yearly ? 'text-gray-900' : 'text-gray-500' }}">
            Yearly <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">2 months free</span>
        </span>
    </div>

    {{-- Plans grid --}}
    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($plans as $plan)
            @php
                $price = $yearly ? $plan->price_yearly : $plan->price_monthly;
                $monthlyDisplay = $yearly ? $plan->price_yearly / 12 : $plan->price_monthly;
                $isCurrent = $currentTier->value === $plan->slug;
                $isPopular = $plan->slug === 'pro';
            @endphp
            <div class="relative rounded-xl bg-white p-6 shadow-sm ring-1 {{ $isPopular ? 'ring-primary-600 ring-2' : 'ring-gray-900/5' }}">
                @if($isPopular)
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary-600 px-3 py-1 text-xs font-semibold text-white">Most Popular</div>
                @endif
                <h3 class="text-lg font-bold text-gray-900">{{ $plan->name }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ $plan->description }}</p>

                <div class="mt-4">
                    @if($price === 0)
                        <span class="text-3xl font-bold text-gray-900">Free</span>
                    @else
                        <span class="text-3xl font-bold text-gray-900">€{{ number_format($monthlyDisplay / 100, 0) }}</span>
                        <span class="text-sm text-gray-500">/month</span>
                    @endif
                </div>

                @php $maintenanceCost = config('qrcode.credits.dynamic_qr_maintenance', 5); @endphp
                <div class="mt-6 space-y-2">
                    <p class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fa-solid fa-coins text-amber-500 w-4 text-center text-xs"></i>
                        @if($plan->slug === 'enterprise')
                            <span class="font-semibold text-gray-900">Unlimited</span> credits
                        @else
                            {{ number_format($plan->monthly_credits) }} credits/month
                        @endif
                    </p>
                    <p class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fa-solid fa-qrcode text-gray-400 w-4 text-center text-xs"></i>
                        {{ $plan->max_static_qr ? $plan->max_static_qr : 'Unlimited' }} static QR codes
                    </p>
                    <p class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fa-solid fa-arrows-rotate text-emerald-500 w-4 text-center text-xs"></i>
                        @if($plan->slug === 'enterprise')
                            <span class="font-semibold text-gray-900">Unlimited</span> dynamic QR codes
                        @else
                            Up to <span class="font-semibold text-gray-900">{{ intdiv($plan->monthly_credits, $maintenanceCost) }}</span> dynamic QR codes
                        @endif
                    </p>
                    @if($plan->slug !== 'enterprise')
                        <p class="text-xs text-gray-400 pl-6">{{ $maintenanceCost }} credits/month each &middot; buy more anytime</p>
                    @endif
                </div>
                <ul class="mt-4 space-y-2 border-t pt-4">
                    @foreach($plan->tier()->featureSummary() as $featureLabel)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="h-4 w-4 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $featureLabel }}
                        </li>
                    @endforeach
                </ul>

                <div class="mt-6">
                    @if($isCurrent)
                        <button disabled class="w-full rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-500 cursor-not-allowed">Current Plan</button>
                    @else
                        <button wire:click="subscribe('{{ $plan->slug }}')"
                                wire:loading.attr="disabled"
                                wire:target="subscribe('{{ $plan->slug }}')"
                                class="w-full rounded-lg {{ $isPopular ? 'bg-primary-600 text-white hover:bg-primary-700' : 'border border-primary-600 text-primary-600 hover:bg-primary-50' }} px-4 py-2.5 text-sm font-semibold transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="subscribe('{{ $plan->slug }}')">
                                {{ $price === 0 ? 'Downgrade to Free' : 'Upgrade' }}
                            </span>
                            <span wire:loading wire:target="subscribe('{{ $plan->slug }}')">Processing…</span>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
