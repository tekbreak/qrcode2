<div class="mx-auto max-w-2xl space-y-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('nav.settings') }}</h1>

    {{-- Profile --}}
    <div class="rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm ring-1 ring-gray-900/5 dark:ring-zinc-800">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('settings.profile') }}</h2>
        <form wire:submit="updateProfile" class="mt-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('auth.name') }}</label>
                <input wire:model="name" type="text" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('auth.email') }}</label>
                <input type="email" value="{{ $email }}" disabled class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800/60 shadow-sm sm:text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('settings.language') }}</label>
                <select wire:model="locale" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <option value="en">{{ __('common.language_en') }}</option>
                    <option value="es">{{ __('common.language_es') }}</option>
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">{{ __('common.save') }}</button>
        </form>
    </div>

    {{-- Password --}}
    <div class="rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm ring-1 ring-gray-900/5 dark:ring-zinc-800">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('settings.change_password') }}</h2>
        <form wire:submit="updatePassword" class="mt-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('settings.current_password') }}</label>
                <input wire:model="current_password" type="password" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('settings.new_password') }}</label>
                <input wire:model="new_password" type="password" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                @error('new_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('settings.confirm_password') }}</label>
                <input wire:model="new_password_confirmation" type="password" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            </div>
            <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">{{ __('settings.update_password') }}</button>
        </form>
    </div>

    {{-- Team management --}}
    <div class="rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm ring-1 ring-gray-900/5 dark:ring-zinc-800">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('settings.team') }}</h2>
        @livewire('teams.team-manager')
    </div>

    {{-- Danger Zone --}}
    <div class="rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm ring-1 ring-red-200 dark:ring-red-900">
        <h2 class="text-lg font-semibold text-red-600">{{ __('settings.danger_zone') }}</h2>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('settings.delete_warning') }}</p>
        <div x-data="{ confirming: false }">
            <button @click="confirming = true" x-show="!confirming" class="mt-4 rounded-lg border border-red-300 dark:border-red-800 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:bg-red-950/50 transition">
                {{ __('settings.delete_account') }}
            </button>
            <div x-show="confirming" class="mt-4 flex items-center gap-3">
                <button wire:click="deleteAccount" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition">
                    {{ __('settings.confirm_delete') }}
                </button>
                <button @click="confirming = false" class="rounded-lg border border-gray-300 dark:border-zinc-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-zinc-800 dark:bg-zinc-800/60 transition">
                    {{ __('common.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>
