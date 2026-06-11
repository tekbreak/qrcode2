<div>
    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('auth.reset_password') }}</h2>

    <form wire:submit="resetPassword" class="mt-6 space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('auth.email') }}</label>
            <input wire:model="email" id="email" type="email" required
                   class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('auth.new_password') }}</label>
            <input wire:model="password" id="password" type="password" required
                   class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('auth.confirm_password') }}</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" required
                   class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
        </div>

        <button type="submit" class="flex w-full justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition">
            {{ __('auth.reset_password') }}
        </button>
    </form>
</div>
