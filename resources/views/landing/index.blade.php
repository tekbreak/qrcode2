<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Create & Track QR Codes</title>
    <meta name="description" content="Create beautiful, trackable QR codes for your business. Dynamic QR codes with real-time analytics, custom designs, and powerful link management.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white antialiased">
    {{-- Navbar --}}
    <nav class="sticky top-0 z-50 border-b bg-white/90 backdrop-blur-md">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="/" class="flex items-center gap-2 text-xl font-bold text-primary-600">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm11-2h2v2h-2v-2zm-3 0h2v2h-2v-2zm6 0h2v2h-2v-2zm-3 3h2v2h-2v-2zm3 0h2v2h-2v-2zm-6 3h2v2h-2v-2zm3 0h2v2h-2v-2zm3 0h2v2h-2v-2z"/></svg>
                {{ config('app.name') }}
            </a>
            <div class="hidden items-center gap-6 sm:flex">
                <a href="#features" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Features</a>
                <a href="#pricing" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Pricing</a>
                <a href="#faq" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">FAQ</a>
            </div>
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">Sign in</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">Get Started Free</a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-primary-50 via-white to-blue-50">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8 lg:py-36">
            <div class="mx-auto max-w-3xl text-center">
                <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
                    Create <span class="text-primary-600">QR Codes</span> That
                    <span class="text-primary-600">Work</span> For You
                </h1>
                <p class="mt-6 text-lg text-gray-600 sm:text-xl">
                    Generate beautiful, customizable QR codes with real-time scan analytics.
                    Dynamic links let you update destinations anytime &mdash; even after printing.
                </p>
                <div class="mt-10 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-xl bg-primary-600 px-8 py-3.5 text-base font-semibold text-white shadow-lg shadow-primary-600/25 hover:bg-primary-700 transition">
                        Start Creating Free
                        <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                    <a href="#features" class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-8 py-3.5 text-base font-semibold text-gray-700 hover:bg-gray-50 transition">Learn More</a>
                </div>
                <p class="mt-4 text-sm text-gray-500">No credit card required. 3 static QR codes + 1 dynamic QR free.</p>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="py-20 sm:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl">Everything You Need for QR Codes</h2>
                <p class="mt-4 text-lg text-gray-600">Powerful tools to create, customize, and track your QR codes.</p>
            </div>
            <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $features = [
                    ['title' => 'Dynamic QR Codes', 'desc' => 'Change the destination URL anytime without reprinting. Perfect for marketing campaigns.', 'icon' => 'link'],
                    ['title' => 'Real-Time Analytics', 'desc' => 'Track scans, locations, devices, and more. Get insights to optimize your campaigns.', 'icon' => 'chart-bar'],
                    ['title' => 'Custom Designs', 'desc' => 'Brand your QR codes with custom colors, logos, and dot styles to match your identity.', 'icon' => 'squares-2x2'],
                    ['title' => 'Multiple QR Types', 'desc' => 'URLs, vCards, WiFi, email, phone, SMS, and more. One platform for all your QR needs.', 'icon' => 'qr-code'],
                    ['title' => 'Link Management', 'desc' => 'Password protection, expiration dates, and scan limits for total control over your links.', 'icon' => 'shield-check'],
                    ['title' => 'Team Collaboration', 'desc' => 'Invite team members, share QR codes, and manage permissions across your organization.', 'icon' => 'cog-6-tooth'],
                ];
                @endphp
                @foreach($features as $feature)
                    <div class="group rounded-2xl border border-gray-200 p-8 transition hover:border-primary-200 hover:shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-100 text-primary-600 transition group-hover:bg-primary-600 group-hover:text-white">
                            <x-icon :name="$feature['icon']" class="h-6 w-6" />
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900">{{ $feature['title'] }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="bg-gray-50 py-20 sm:py-28">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-bold text-gray-900 sm:text-4xl">Simple, Transparent Pricing</h2>
                <p class="mt-4 text-lg text-gray-600">Start free and scale as you grow. No hidden fees.</p>
            </div>

            <div x-data="{ yearly: false }" class="mt-10">
                <div class="flex items-center justify-center gap-3">
                    <span class="text-sm font-medium" :class="!yearly ? 'text-gray-900' : 'text-gray-500'">Monthly</span>
                    <button @click="yearly = !yearly" type="button" role="switch"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors"
                            :class="yearly ? 'bg-primary-600' : 'bg-gray-200'">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition" :class="yearly ? 'translate-x-5' : 'translate-x-0'"></span>
                    </button>
                    <span class="text-sm font-medium" :class="yearly ? 'text-gray-900' : 'text-gray-500'">
                        Yearly <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">2 months free</span>
                    </span>
                </div>

                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @php
                    $tiers = \App\Enums\PlanTier::cases();
                    $maintenanceCost = config('qrcode.credits.dynamic_qr_maintenance', 5);
                    $plans = [
                        ['tier' => $tiers[0], 'monthly' => 0, 'yearly_total' => 0, 'credits' => 5, 'static_limit' => 3, 'popular' => false],
                        ['tier' => $tiers[1], 'monthly' => 5, 'yearly_total' => 50, 'credits' => 50, 'static_limit' => 10, 'popular' => false],
                        ['tier' => $tiers[2], 'monthly' => 15, 'yearly_total' => 150, 'credits' => 200, 'static_limit' => 50, 'popular' => true],
                        ['tier' => $tiers[3], 'monthly' => 50, 'yearly_total' => 500, 'credits' => null, 'static_limit' => null, 'popular' => false],
                    ];
                    @endphp
                    @foreach($plans as $plan)
                        <div class="relative rounded-2xl bg-white p-8 shadow-sm ring-1 {{ ($plan['popular'] ?? false) ? 'ring-primary-600 ring-2' : 'ring-gray-200' }}">
                            @if($plan['popular'] ?? false)
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-primary-600 px-4 py-1 text-xs font-semibold text-white">Most Popular</div>
                            @endif
                            <h3 class="text-xl font-bold text-gray-900">{{ $plan['tier']->label() }}</h3>
                            <div class="mt-4">
                                @if($plan['monthly'] === 0)
                                    <span class="text-4xl font-bold text-gray-900">Free</span>
                                @else
                                    <span x-show="!yearly" class="text-4xl font-bold text-gray-900">€{{ $plan['monthly'] }}</span>
                                    <span x-show="yearly" x-cloak class="text-4xl font-bold text-gray-900">€{{ $plan['yearly_total'] }}</span>
                                    <span class="text-sm text-gray-500" x-text="yearly ? '/year' : '/month'">/month</span>
                                @endif
                            </div>
                            @if($plan['monthly'] > 0)
                                <p x-show="yearly" x-cloak class="mt-1 text-xs text-green-600 font-medium">
                                    €{{ number_format($plan['yearly_total'] / 12, 2) }}/mo &middot; Save €{{ ($plan['monthly'] * 12) - $plan['yearly_total'] }}/year
                                </p>
                            @endif
                            <div class="mt-3 space-y-1">
                                <p class="flex items-center gap-1.5 text-sm text-gray-600">
                                    <i class="fa-solid fa-coins text-amber-500 w-4 text-center text-xs"></i>
                                    @if($plan['credits'] === null)
                                        <span class="font-semibold text-gray-900">Unlimited</span> credits
                                    @else
                                        {{ $plan['credits'] }} credits/month
                                    @endif
                                </p>
                                <p class="flex items-center gap-1.5 text-sm text-gray-600">
                                    <i class="fa-solid fa-qrcode text-gray-400 w-4 text-center text-xs"></i>
                                    {{ $plan['static_limit'] === null ? 'Unlimited' : $plan['static_limit'] }} static QR codes
                                </p>
                                <p class="flex items-center gap-1.5 text-sm text-gray-600">
                                    <i class="fa-solid fa-arrows-rotate text-emerald-500 w-4 text-center text-xs"></i>
                                    @if($plan['credits'] === null)
                                        <span class="font-semibold text-gray-900">Unlimited</span> dynamic QR codes
                                    @else
                                        Up to <span class="font-semibold text-gray-900">{{ intdiv($plan['credits'], $maintenanceCost) }}</span> dynamic QR codes
                                    @endif
                                </p>
                                @if($plan['credits'] !== null)
                                    <p class="text-xs text-gray-400 pl-5.5">{{ $maintenanceCost }} credits/month each &middot; buy more anytime</p>
                                @endif
                            </div>
                            <ul class="mt-5 space-y-2.5 border-t pt-5">
                                @foreach($plan['tier']->featureSummary() as $featureLabel)
                                    <li class="flex items-center gap-2 text-sm text-gray-600">
                                        <svg class="h-4 w-4 shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        {{ $featureLabel }}
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('register') }}"
                               class="mt-8 block w-full rounded-xl {{ ($plan['popular'] ?? false) ? 'bg-primary-600 text-white hover:bg-primary-700' : 'border border-gray-300 text-gray-700 hover:bg-gray-50' }} px-4 py-3 text-center text-sm font-semibold transition">
                                {{ $plan['monthly'] === 0 ? 'Get Started Free' : 'Get Started' }}
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
            <h2 class="text-center text-3xl font-bold text-gray-900 sm:text-4xl">Frequently Asked Questions</h2>
            <div class="mt-12 space-y-4" x-data="{ open: null }">
                @php
                $faqs = [
                    ['q' => 'What is a dynamic QR code?', 'a' => 'A dynamic QR code redirects through our servers, allowing you to change the destination URL anytime without reprinting the code. It also enables scan tracking and analytics.'],
                    ['q' => 'What are credits and how do they work?', 'a' => 'Credits are used for maintaining dynamic QR codes (5 credits/month each), editing them (5 credits per edit), and premium features like vector downloads, API access, and analytics export. Each plan includes a monthly credit allowance that resets every billing cycle. You can also purchase additional credit packs anytime.'],
                    ['q' => 'Can I change the URL after printing my QR code?', 'a' => 'Yes! Dynamic QR codes let you change the destination URL as many times as you want, even after the code has been printed on physical materials.'],
                    ['q' => 'What analytics do you provide?', 'a' => 'We track total scans, unique scans, geographic locations, device types, operating systems, browsers, referrers, and provide time-series charts to visualize trends.'],
                    ['q' => 'Do you offer custom domains?', 'a' => 'Yes, Pro and Enterprise plans support custom domains for your short links. Instead of go.oursite.com, your QR codes can redirect through your own branded domain.'],
                    ['q' => 'Can I cancel my subscription anytime?', 'a' => 'Absolutely. You can cancel anytime and will retain access until the end of your billing period. Your QR codes will continue to work, but dynamic features will be limited to the free tier.'],
                ];
                @endphp
                @foreach($faqs as $i => $faq)
                    <div class="rounded-xl border border-gray-200">
                        <button @click="open = open === {{ $i }} ? null : {{ $i }}" class="flex w-full items-center justify-between px-6 py-4 text-left">
                            <span class="text-sm font-semibold text-gray-900">{{ $faq['q'] }}</span>
                            <svg class="h-5 w-5 shrink-0 text-gray-500 transition" :class="open === {{ $i }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open === {{ $i }}" x-collapse class="px-6 pb-4">
                            <p class="text-sm text-gray-600">{{ $faq['a'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="bg-primary-600 py-16">
        <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white sm:text-4xl">Ready to Create Your First QR Code?</h2>
            <p class="mt-4 text-lg text-primary-100">Join thousands of businesses using our platform. Start free today.</p>
            <a href="{{ route('register') }}" class="mt-8 inline-flex items-center rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-primary-600 shadow-lg hover:bg-gray-50 transition">
                Get Started Free
                <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        </div>
    </section>

    <x-cookie-consent />

    {{-- Footer --}}
    <footer class="border-t bg-gray-50 py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <a href="/" class="flex items-center gap-2 text-lg font-bold text-primary-600">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5z"/></svg>
                        {{ config('app.name') }}
                    </a>
                    <p class="mt-3 text-sm text-gray-500">Create beautiful, trackable QR codes for your business.</p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900">Product</h4>
                    <ul class="mt-3 space-y-2">
                        <li><a href="#features" class="text-sm text-gray-500 hover:text-gray-900">Features</a></li>
                        <li><a href="#pricing" class="text-sm text-gray-500 hover:text-gray-900">Pricing</a></li>
                        <li><a href="#faq" class="text-sm text-gray-500 hover:text-gray-900">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900">Legal</h4>
                    <ul class="mt-3 space-y-2">
                        <li><a href="#" class="text-sm text-gray-500 hover:text-gray-900">Privacy Policy</a></li>
                        <li><a href="#" class="text-sm text-gray-500 hover:text-gray-900">Terms of Service</a></li>
                        <li><a href="#" class="text-sm text-gray-500 hover:text-gray-900">Cookie Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900">Support</h4>
                    <ul class="mt-3 space-y-2">
                        <li><a href="#" class="text-sm text-gray-500 hover:text-gray-900">Help Center</a></li>
                        <li><a href="#" class="text-sm text-gray-500 hover:text-gray-900">Contact Us</a></li>
                        <li><a href="#" class="text-sm text-gray-500 hover:text-gray-900">Status</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t pt-8 text-center text-sm text-gray-400">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
