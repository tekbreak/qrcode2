<div class="mx-auto max-w-2xl space-y-8">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('nav.settings') }}</h1>

    {{-- Profile --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
        <h2 class="text-lg font-semibold text-gray-900">Profile</h2>
        <form wire:submit="updateProfile" class="mt-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('auth.name') }}</label>
                <input wire:model="name" type="text" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('auth.email') }}</label>
                <input type="email" value="{{ $email }}" disabled class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50 shadow-sm sm:text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Language</label>
                <select wire:model="locale" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    <option value="en">English</option>
                    <option value="es">Espa&ntilde;ol</option>
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">{{ __('common.save') }}</button>
        </form>
    </div>

    {{-- Password --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
        <h2 class="text-lg font-semibold text-gray-900">Change Password</h2>
        <form wire:submit="updatePassword" class="mt-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Current Password</label>
                <input wire:model="current_password" type="password" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">New Password</label>
                <input wire:model="new_password" type="password" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                @error('new_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                <input wire:model="new_password_confirmation" type="password" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
            </div>
            <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">Update Password</button>
        </form>
    </div>

    {{-- Team management --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
        <h2 class="text-lg font-semibold text-gray-900">Team</h2>
        @livewire('teams.team-manager')
    </div>

    {{-- Danger Zone --}}
    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-red-200">
        <h2 class="text-lg font-semibold text-red-600">Danger Zone</h2>
        <p class="mt-2 text-sm text-gray-500">Once you delete your account, all data will be permanently removed.</p>
        <div x-data="{ confirming: false }">
            <button @click="confirming = true" x-show="!confirming" class="mt-4 rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition">
                Delete Account
            </button>
            <div x-show="confirming" class="mt-4 flex items-center gap-3">
                <button wire:click="deleteAccount" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 transition">
                    Yes, delete my account
                </button>
                <button @click="confirming = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    {{ __('common.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>
