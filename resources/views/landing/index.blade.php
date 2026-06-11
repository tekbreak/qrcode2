<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title>{{ config('app.name') }} - {{ __('landing.meta_title') }}</title>
    <meta name="description" content="{{ __('landing.meta_description') }}">
    @include('partials.theme-init')
    @include('partials.exclusive-dropdown')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white antialiased dark:bg-zinc-950">
    {{-- Navbar --}}
    <nav class="sticky top-0 z-50 border-b border-gray-200 bg-white/90 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-950/90">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="/" class="flex items-center gap-2 text-xl font-bold text-primary-600 dark:text-primary-400">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm11-2h2v2h-2v-2zm-3 0h2v2h-2v-2zm6 0h2v2h-2v-2zm-3 3h2v2h-2v-2zm3 0h2v2h-2v-2zm-6 3h2v2h-2v-2zm3 0h2v2h-2v-2zm3 0h2v2h-2v-2z"/></svg>
                {{ config('app.name') }}
            </a>
            <div class="hidden items-center gap-6 sm:flex">
                <a href="#features" class="text-sm font-medium text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">{{ __('landing.nav.features') }}</a>
                <a href="#pricing" class="text-sm font-medium text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">{{ __('landing.nav.pricing') }}</a>
                <a href="#faq" class="text-sm font-medium text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">{{ __('landing.nav.faq') }}</a>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <x-theme-switcher />
                <x-language-switcher />
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">{{ __('landing.nav.dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">{{ __('landing.nav.sign_in') }}</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">{{ __('landing.nav.get_started') }}</a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-primary-50 via-white to-blue-50 dark:from-zinc-900 dark:via-zinc-950 dark:to-zinc-900">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8 lg:py-36">
            <div class="mx-auto max-w-3xl text-center">
                <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl dark:text-gray-100">
                    {{ __('landing.hero.title_prefix') }} <span class="text-primary-600 dark:text-primary-400">{{ __('landing.hero.title_highlight_1') }}</span> {{ __('landing.hero.title_middle') }}
                    <span class="text-primary-600 dark:text-primary-400">{{ __('landing.hero.title_highlight_2') }}</span> {{ __('landing.hero.title_suffix') }}
                </h1>
                <p class="mt-6 text-lg text-gray-600 sm:text-xl dark:text-gray-400">
                    {{ __('landing.hero.subtitle') }}
                </p>
                <div class="mt-10 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-xl bg-primary-600 px-8 py-3.5 text-base font-semibold text-white shadow-lg shadow-primary-600/25 transition hover:bg-primary-700">
                        {{ __('landing.hero.cta_primary') }}
                        <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                    <a href="#features" class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-8 py-3.5 text-base font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-gray-300 dark:hover:bg-zinc-800">{{ __('landing.hero.cta_secondary') }}</a>
                </div>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('landing.hero.footnote') }}</p>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="py-20 sm:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl dark:text-gray-100">{{ __('landing.features.title') }}</h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">{{ __('landing.features.subtitle') }}</p>
            </div>
            <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $featureKeys = ['dynamic', 'analytics', 'designs', 'types', 'links', 'teams'];
                $featureIcons = ['link', 'chart-bar', 'squares-2x2', 'qr-code', 'shield-check', 'cog-6-tooth'];
                @endphp
                @foreach($featureKeys as $index => $key)
                    <div class="group rounded-2xl border border-gray-200 p-8 transition hover:border-primary-200 hover:shadow-lg dark:border-zinc-800 dark:hover:border-primary-800 dark:hover:shadow-black/20">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-100 text-primary-600 transition group-hover:bg-primary-600 group-hover:text-white dark:bg-primary-950 dark:text-primary-400 dark:group-hover:bg-primary-600 dark:group-hover:text-white">
                            <x-icon :name="$featureIcons[$index]" class="h-6 w-6" />
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('landing.features.items.'.$key.'.title') }}</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('landing.features.items.'.$key.'.desc') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="bg-gray-50 py-20 sm:py-28 dark:bg-zinc-900/50">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl dark:text-gray-100">{{ __('landing.pricing.title') }}</h2>
                <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">{{ __('landing.pricing.subtitle') }}</p>
            </div>

            <div x-data="{ yearly: false }" class="mt-10">
                <div class="flex items-center justify-center gap-3">
                    <span class="text-sm font-medium" :class="!yearly ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'">{{ __('landing.pricing.monthly') }}</span>
                    <button @click="yearly = !yearly" type="button" role="switch"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors"
                            :class="yearly ? 'bg-primary-600' : 'bg-gray-200 dark:bg-zinc-700'">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition" :class="yearly ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                    <span class="text-sm font-medium" :class="yearly ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400'">
                        {{ __('landing.pricing.yearly') }} <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-950 dark:text-green-400">{{ __('landing.pricing.yearly_badge') }}</span>
                    </span>
                </div>

                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @php
                    $plans = [
                        ['tier' => \App\Enums\PlanTier::Starter, 'monthly' => 0, 'yearly_total' => 0, 'static_limit' => 5, 'dynamic_limit' => 1, 'popular' => false],
                        ['tier' => \App\Enums\PlanTier::Pro, 'monthly' => 10, 'yearly_total' => 99, 'static_limit' => null, 'dynamic_limit' => 10, 'popular' => true],
                        ['tier' => \App\Enums\PlanTier::Enterprise, 'monthly' => 39, 'yearly_total' => 389, 'static_limit' => null, 'dynamic_limit' => null, 'popular' => false],
                    ];
                    @endphp
                    @foreach($plans as $plan)
                        <div class="relative rounded-2xl bg-white p-8 shadow-sm ring-1 dark:bg-zinc-900 {{ ($plan['popular'] ?? false) ? 'ring-primary-600 ring-2 dark:ring-primary-500' : 'ring-gray-200 dark:ring-zinc-800' }}">
                            @if($plan['popular'] ?? false)
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary-600 px-4 py-1 text-xs font-semibold text-white">{{ __('landing.pricing.most_popular') }}</div>
                            @endif
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $plan['tier']->label() }}</h3>
                            <div class="mt-4">
                                @if($plan['monthly'] === 0)
                                    <span class="text-4xl font-bold text-gray-900 dark:text-gray-100">{{ __('landing.pricing.free') }}</span>
                                @else
                                    <span x-show="!yearly" class="text-4xl font-bold text-gray-900 dark:text-gray-100">€{{ $plan['monthly'] }}</span>
                                    <span x-show="yearly" x-cloak class="text-4xl font-bold text-gray-900 dark:text-gray-100">€{{ $plan['yearly_total'] }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400" x-text="yearly ? '{{ __('landing.pricing.per_year') }}' : '{{ __('landing.pricing.per_month') }}'">{{ __('landing.pricing.per_month') }}</span>
                                @endif
                            </div>
                            @if($plan['monthly'] > 0)
                                <p x-show="yearly" x-cloak class="mt-1 text-xs font-medium text-green-600 dark:text-green-400">
                                    {{ __('landing.pricing.yearly_savings', ['monthly' => number_format($plan['yearly_total'] / 12, 2), 'savings' => ($plan['monthly'] * 12) - $plan['yearly_total']]) }}
                                </p>
                            @endif
                            <div class="mt-3 space-y-1">
                                <p class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fa-solid fa-qrcode w-4 text-center text-xs text-gray-400"></i>
                                    @if($plan['static_limit'] === null)
                                        {{ __('landing.pricing.static_unlimited') }}
                                    @else
                                        {{ __('landing.pricing.static_limit', ['count' => $plan['static_limit']]) }}
                                    @endif
                                </p>
                                <p class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fa-solid fa-arrows-rotate w-4 text-center text-xs text-emerald-500"></i>
                                    @if($plan['dynamic_limit'] === null)
                                        {{ __('landing.pricing.dynamic_unlimited') }}
                                    @else
                                        {{ __('landing.pricing.dynamic_limit', ['count' => $plan['dynamic_limit']]) }}
                                    @endif
                                </p>
                                @if($plan['tier'] === \App\Enums\PlanTier::Enterprise)
                                    <p class="pl-5.5 text-xs text-gray-400">{{ __('landing.pricing.edits_included') }}</p>
                                @else
                                    <p class="pl-5.5 text-xs text-gray-400">{{ __('landing.pricing.paid_edits_note') }}</p>
                                @endif
                            </div>
                            <ul class="mt-5 space-y-2.5 border-t border-gray-200 pt-5 dark:border-zinc-800">
                                @foreach($plan['tier']->featureSummary() as $featureLabel)
                                    <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <svg class="h-4 w-4 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        {{ $featureLabel }}
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('register') }}"
                               class="mt-8 block w-full rounded-xl px-4 py-3 text-center text-sm font-semibold transition {{ ($plan['popular'] ?? false) ? 'bg-primary-600 text-white hover:bg-primary-700' : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-zinc-700 dark:text-gray-300 dark:hover:bg-zinc-800' }}">
                                {{ $plan['monthly'] === 0 ? __('landing.pricing.cta_free') : __('landing.pricing.cta_paid') }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="py-20 sm:py-28">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <h2 class="text-center text-3xl font-bold text-gray-900 sm:text-4xl dark:text-gray-100">{{ __('landing.faq.title') }}</h2>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqKeys = ['dynamic', 'paid_edits', 'url_change', 'analytics', 'domains', 'cancel'];
                @endphp
                @foreach($faqKeys as $i => $key)
                    <div class="rounded-xl border border-gray-200 dark:border-zinc-800">
                        <button @click="open = open === {{ $i }} ? null : {{ $i }}" class="flex w-full items-center justify-between px-6 py-4 text-left">
                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('landing.faq.items.'.$key.'.q') }}</span>
                            <svg class="h-5 w-5 shrink-0 text-gray-500 transition dark:text-gray-400" :class="open === {{ $i }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open === {{ $i }}" x-collapse class="px-6 pb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('landing.faq.items.'.$key.'.a') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-primary-600 py-16 dark:bg-primary-700">
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white sm:text-4xl">{{ __('landing.cta.title') }}</h2>
            <p class="mt-4 text-lg text-primary-100">{{ __('landing.cta.subtitle') }}</p>
            <a href="{{ route('register') }}" class="mt-8 inline-flex items-center rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-primary-600 shadow-lg transition hover:bg-gray-50">
                {{ __('landing.cta.button') }}
                <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </section>

    <x-cookie-consent />

    {{-- Footer --}}
    <footer class="border-t border-gray-200 bg-gray-50 py-12 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <a href="/" class="flex items-center gap-2 text-lg font-bold text-primary-600 dark:text-primary-400">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5z"/></svg>
                        {{ config('app.name') }}
                    </a>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('landing.footer.tagline') }}</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('landing.footer.product') }}</h4>
                    <ul class="mt-3 space-y-2">
                        <li><a href="#features" class="text-sm text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">{{ __('landing.nav.features') }}</a></li>
                        <li><a href="#pricing" class="text-sm text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">{{ __('landing.nav.pricing') }}</a></li>
                        <li><a href="#faq" class="text-sm text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">{{ __('landing.nav.faq') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('landing.footer.legal') }}</h4>
                    <ul class="mt-3 space-y-2">
                        <li><a href="#" class="text-sm text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">{{ __('landing.footer.privacy') }}</a></li>
                        <li><a href="#" class="text-sm text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">{{ __('landing.footer.terms') }}</a></li>
                        <li><a href="#" class="text-sm text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">{{ __('landing.footer.cookies') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('landing.footer.support') }}</h4>
                    <ul class="mt-3 space-y-2">
                        <li><a href="#" class="text-sm text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">{{ __('landing.footer.help') }}</a></li>
                        <li><a href="#" class="text-sm text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">{{ __('landing.footer.contact') }}</a></li>
                        <li><a href="#" class="text-sm text-gray-500 transition hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">{{ __('landing.footer.status') }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t border-gray-200 pt-8 text-center text-sm text-gray-400 dark:border-zinc-800">
                &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('landing.footer.copyright') }}
            </div>
        </div>
    </footer>
</body>
</html>
