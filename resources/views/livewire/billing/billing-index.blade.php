<div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('nav.billing') }}</h1>

    @if($successMessage)
        <div class="mt-4 rounded-lg bg-green-50 dark:bg-green-950/50 p-4 text-sm text-green-700 dark:text-green-400">
            {{ $successMessage }}
        </div>
    @elseif(session('success') || request('success'))
        <div class="mt-4 rounded-lg bg-green-50 dark:bg-green-950/50 p-4 text-sm text-green-700 dark:text-green-400">
            {{ session('success') ?: 'Subscription updated successfully!' }}
        </div>
    @endif

    @if($errorMessage)
        <div class="mt-4 rounded-lg bg-red-50 dark:bg-red-950/50 p-4 text-sm text-red-700 dark:text-red-400">{{ $errorMessage }}</div>
    @elseif(session('error'))
        <div class="mt-4 rounded-lg bg-red-50 dark:bg-red-950/50 p-4 text-sm text-red-700 dark:text-red-400">{{ session('error') }}</div>
    @endif

    {{-- Current plan --}}
    <div class="mt-6 rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm ring-1 ring-gray-900/5 dark:ring-zinc-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Current Plan</p>
                <p class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $currentTier->label() }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if($currentTier === \App\Enums\PlanTier::Enterprise)
                        Unlimited dynamic QR edits included
                    @else
                        Dynamic QR edits: €1 per action
                    @endif
                </p>
                @if($canViewInvoices)
                    <button wire:click="manageBilling" type="button"
                            class="mt-2 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition">
                        View invoices & receipts
                    </button>
                @endif
            </div>
            @if($isSubscribed)
                <button wire:click="manageBilling" class="rounded-lg border border-gray-300 dark:border-zinc-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                    Manage Billing
                </button>
            @endif
        </div>
    </div>

    {{-- Billing toggle (Enterprise yearly only) --}}
    <div class="mt-8 flex items-center justify-center gap-3">
        <span class="text-sm font-medium {{ !$yearly ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' }}">Monthly</span>
        <button wire:click="$toggle('yearly')" type="button" role="switch"
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 {{ $yearly ? 'bg-primary-600' : 'bg-gray-200 dark:bg-zinc-700' }}"
                aria-checked="{{ $yearly ? 'true' : 'false' }}">
            <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white dark:bg-zinc-900 shadow ring-0 transition duration-200 {{ $yearly ? 'translate-x-5' : 'translate-x-0' }}"></span>
        </button>
        <span class="text-sm font-medium {{ $yearly ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' }}">
            Yearly <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">Save on annual billing</span>
        </span>
    </div>

    {{-- Plans grid --}}
    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($plans as $plan)
            @php
                $price = $yearly && $plan->price_yearly > 0 ? $plan->price_yearly : $plan->price_monthly;
                $monthlyDisplay = $yearly && $plan->price_yearly > 0 ? $plan->price_yearly / 12 : $plan->price_monthly;
                $isCurrent = $currentTier->value === $plan->slug;
                $isPopular = $plan->slug === 'pro';
            @endphp
            <div class="relative rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm ring-1 {{ $isPopular ? 'ring-primary-600 ring-2' : 'ring-gray-900/5 dark:ring-zinc-800' }}">
                @if($isPopular)
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary-600 px-3 py-1 text-xs font-semibold text-white">Most Popular</div>
                @endif
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $plan->name }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $plan->description }}</p>

                <div class="mt-4">
                    @if($price === 0)
                        <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">Free</span>
                    @else
                        <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">€{{ number_format($monthlyDisplay / 100, 0) }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">/month</span>
                        @if($yearly && $plan->price_yearly > 0)
                            <p class="mt-1 text-xs text-green-600 dark:text-green-400">Billed €{{ number_format($plan->price_yearly / 100, 0) }}/year</p>
                        @endif
                    @endif
                </div>

                <div class="mt-6 space-y-2">
                    <p class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <i class="fa-solid fa-qrcode text-gray-400 w-4 text-center text-xs"></i>
                        {{ $plan->max_static_qr ? $plan->max_static_qr : 'Unlimited' }} static QR codes
                    </p>
                    <p class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <i class="fa-solid fa-arrows-rotate text-emerald-500 w-4 text-center text-xs"></i>
                        @if($plan->max_dynamic_qr === null)
                            <span class="font-semibold text-gray-900 dark:text-gray-100">Unlimited</span> dynamic QR codes
                        @else
                            {{ $plan->max_dynamic_qr }} dynamic QR {{ $plan->max_dynamic_qr === 1 ? 'code' : 'codes' }}
                        @endif
                    </p>
                    @if($plan->slug !== 'enterprise')
                        <p class="text-xs text-gray-400 pl-6">€1 per dynamic QR edit after creation</p>
                    @else
                        <p class="text-xs text-gray-400 pl-6">Unlimited dynamic QR edits included</p>
                    @endif
                </div>
                <ul class="mt-4 space-y-2 border-t pt-4">
                    @foreach($plan->tier()->featureSummary() as $featureLabel)
                        <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <svg class="h-4 w-4 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $featureLabel }}
                        </li>
                    @endforeach
                </ul>

                <div class="mt-6">
                    @if($isCurrent)
                        <button disabled class="w-full rounded-lg bg-gray-100 dark:bg-zinc-800 px-4 py-2.5 text-sm font-medium text-gray-500 cursor-not-allowed">Current Plan</button>
                    @else
                        <button wire:click="subscribe('{{ $plan->slug }}')"
                                wire:loading.attr="disabled"
                                wire:target="subscribe('{{ $plan->slug }}')"
                                class="w-full rounded-lg {{ $isPopular ? 'bg-primary-600 text-white hover:bg-primary-700' : 'border border-primary-600 text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-950/50' }} px-4 py-2.5 text-sm font-semibold transition disabled:opacity-50">
                            <span wire:loading.remove wire:target="subscribe('{{ $plan->slug }}')">
                                {{ $price === 0 ? 'Downgrade to Starter' : 'Upgrade' }}
                            </span>
                            <span wire:loading wire:target="subscribe('{{ $plan->slug }}')">Processing…</span>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
