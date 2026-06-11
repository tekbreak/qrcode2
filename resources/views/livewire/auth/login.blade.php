<div>
    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('auth.login') }}</h2>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('auth.login_subtitle') }}</p>

    {{-- Social login --}}
    <div class="mt-6">
        <a href="{{ route('auth.google.redirect') }}" class="flex w-full items-center justify-center gap-3 rounded-lg border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-zinc-800 dark:bg-zinc-800/60 transition">
            <svg class="h-5 w-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            {{ __('auth.continue_with_google') }}
        </a>
    </div>

    <div class="relative mt-6">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-300 dark:border-zinc-700"></div></div>
        <div class="relative flex justify-center text-sm"><span class="bg-white dark:bg-zinc-900 px-4 text-gray-500 dark:text-gray-400">{{ __('auth.or') }}</span></div>
    </div>

    {{-- Email/Password form --}}
    <form wire:submit="login" class="mt-6 space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('auth.email') }}</label>
            <input wire:model="email" id="email" type="email" autocomplete="email" required
                   class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('auth.password') }}</label>
            <input wire:model="password" id="password" type="password" autocomplete="current-password" required
                   class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2">
                <input wire:model="remember" type="checkbox" class="rounded border-gray-300 dark:border-zinc-700 text-primary-600 focus:ring-primary-500">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('auth.remember_me') }}</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500">{{ __('auth.forgot_password') }}</a>
        </div>

        <button type="submit" class="flex w-full justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
            <span wire:loading.remove>{{ __('auth.sign_in') }}</span>
            <span wire:loading>{{ __('common.loading') }}...</span>
        </button>
    </form>

    {{-- Magic link --}}
    <div class="mt-6 border-t pt-6">
        <form action="{{ route('auth.magic-link.send') }}" method="POST" class="flex gap-2">
            @csrf
            <input name="email" type="email" placeholder="{{ __('auth.magic_link_placeholder') }}"
                   class="flex-1 rounded-lg border-gray-300 dark:border-zinc-700 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <button type="submit" class="rounded-lg border border-primary-600 px-4 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50 transition">
                {{ __('auth.send_magic_link') }}
            </button>
        </form>
        @if(session('status'))
            <p class="mt-2 text-sm text-green-600">{{ session('status') }}</p>
        @endif
    </div>

    <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
        {{ __('auth.no_account') }}
        <a href="{{ route('register') }}" class="font-medium text-primary-600 hover:text-primary-500">{{ __('auth.sign_up') }}</a>
    </p>

    @if ($mockAccounts)
        <div class="mt-6 rounded-lg border border-dashed border-amber-300 bg-amber-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">{{ __('auth.dev_login_title') }}</p>
            <p class="mt-1 text-xs text-amber-700">{{ __('auth.dev_login_subtitle') }}</p>
            <div class="mt-3 flex flex-col gap-2">
                @foreach ($mockAccounts as $account)
                    <button
                        type="button"
                        wire:click="quickLogin('{{ $account['email'] }}')"
                        class="flex w-full items-center justify-between rounded-lg border border-amber-200 bg-white dark:bg-zinc-900 px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 shadow-sm transition hover:bg-amber-100"
                    >
                        <span class="font-medium">{{ $account['name'] }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $account['role_label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>
