<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $title ?? 'Dashboard' }} - {{ config('app.name') }}</title>
    @include('partials.theme-init')
    @include('partials.exclusive-dropdown')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 antialiased dark:bg-zinc-950" x-data="{ sidebarOpen: false }">
    {{-- Mobile sidebar backdrop --}}
    <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-gray-900/80 lg:hidden" @click="sidebarOpen = false"></div>

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 w-64 transform bg-white shadow-lg transition-transform duration-300 dark:bg-zinc-900 dark:shadow-black/20 lg:translate-x-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="flex h-16 items-center gap-2 border-b border-gray-200 px-6 dark:border-zinc-800">
            <svg class="h-7 w-7 text-primary-600" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm11-2h2v2h-2v-2zm-3 0h2v2h-2v-2zm6 0h2v2h-2v-2zm-3 3h2v2h-2v-2zm3 0h2v2h-2v-2zm-6 3h2v2h-2v-2zm3 0h2v2h-2v-2zm3 0h2v2h-2v-2z"/>
            </svg>
            <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ config('app.name') }}</span>
        </div>
        <nav class="mt-4 space-y-1 px-3">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-zinc-800' }}">
                <x-icon name="squares-2x2" class="h-5 w-5" />
                {{ __('nav.dashboard') }}
            </a>
            <a href="{{ route('qr-codes.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('qr-codes.*') ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-zinc-800' }}">
                <x-icon name="qr-code" class="h-5 w-5" />
                {{ __('nav.qr_codes') }}
            </a>
            <a href="{{ route('analytics.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('analytics.*') ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-zinc-800' }}">
                <x-icon name="chart-bar" class="h-5 w-5" />
                {{ __('nav.analytics') }}
            </a>
            <a href="{{ route('billing.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('billing.*') ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-zinc-800' }}">
                <x-icon name="credit-card" class="h-5 w-5" />
                {{ __('nav.billing') }}
            </a>
            <a href="{{ route('settings.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('settings.*') ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-zinc-800' }}">
                <x-icon name="cog-6-tooth" class="h-5 w-5" />
                {{ __('nav.settings') }}
            </a>

            @if(auth()->user()?->is_admin)
            <div class="my-3 border-t border-gray-200 pt-3 dark:border-zinc-800">
                <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('nav.admin') }}</p>
                <a href="{{ route('admin.dashboard') }}"
                   class="mt-2 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.*') ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-zinc-800' }}">
                    <x-icon name="shield-check" class="h-5 w-5" />
                    {{ __('nav.admin_panel') }}
                </a>
            </div>
            @endif
        </nav>

    </aside>

    {{-- Main content --}}
    <div class="lg:pl-64">
        {{-- Top bar --}}
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6 dark:border-zinc-800 dark:bg-zinc-900">
            <button @click="sidebarOpen = true" class="text-gray-500 dark:text-gray-400 lg:hidden">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="flex-1"></div>
            <div class="flex items-center gap-2 sm:gap-3">
                <x-theme-switcher />
                <x-language-switcher />
                <a href="{{ route('qr-codes.create') }}" class="hidden sm:inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('nav.new_qr_code') }}
                </a>
                <div
                    x-data="{
                        ...exclusiveDropdownMixin('user'),
                        init() { this._dropdownInit(); }
                    }"
                    @click.outside="closeDropdown()"
                    class="relative"
                >
                    <button @click.stop="toggleDropdown()" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-zinc-800">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-primary-700 font-semibold text-sm dark:bg-primary-950 dark:text-primary-400">
                            {{ substr(auth()->user()?->name ?? 'U', 0, 1) }}
                        </div>
                        <span class="hidden sm:block">{{ auth()->user()?->name }}</span>
                    </button>
                    <div x-show="open" x-cloak x-transition
                         class="absolute right-0 mt-2 w-48 rounded-lg bg-white py-1 shadow-lg ring-1 ring-gray-900/5 dark:bg-zinc-900 dark:ring-zinc-700">
                        <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-zinc-800">{{ __('nav.settings') }}</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-zinc-800">{{ __('nav.logout') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Page content --}}
        <main class="p-4 sm:p-6 lg:p-8">
            @if(session('status'))
                <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-700 dark:bg-green-950/50 dark:text-green-400">
                    {{ session('status') }}
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>
    <x-cookie-consent />
    @livewireScripts
    @stack('scripts')
</body>
</html>
