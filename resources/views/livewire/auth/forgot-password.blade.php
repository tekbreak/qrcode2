<div>
    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('auth.forgot_password') }}</h2>

    @unless($linkSent)
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('auth.forgot_password_subtitle') }}</p>
    @endunless

    @if($linkSent)
        <div class="mt-4 rounded-lg bg-green-50 dark:bg-green-950/50 p-4 text-sm text-green-700 dark:text-green-400">{{ __('passwords.sent') }}</div>
    @endif

    @unless($linkSent)
        <form wire:submit="sendResetLink" class="mt-6 space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('auth.email') }}</label>
                <input wire:model="email" id="email" type="email" autocomplete="email" required
                       class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="flex w-full justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
                {{ __('auth.send_reset_link') }}
            </button>
        </form>
    @endunless

    <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
        <a href="{{ route('login') }}" class="font-medium text-primary-600 hover:text-primary-500">{{ __('auth.back_to_login') }}</a>
    </p>
</div>
