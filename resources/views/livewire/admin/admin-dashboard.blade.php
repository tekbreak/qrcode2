<div>
    <h1 class="text-2xl font-bold text-gray-900">{{ __('nav.admin_panel') }}</h1>

    {{-- Tabs --}}
    <div class="mt-6 border-b">
        <nav class="flex gap-6">
            <button wire:click="$set('tab', 'overview')" class="border-b-2 pb-3 text-sm font-medium transition {{ $tab === 'overview' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Overview</button>
            <button wire:click="$set('tab', 'users')" class="border-b-2 pb-3 text-sm font-medium transition {{ $tab === 'users' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">Users</button>
        </nav>
    </div>

    @if($tab === 'overview')
    {{-- Stats --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
            <p class="text-sm text-gray-500">Total Users</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_users']) }}</p>
            <p class="mt-1 text-xs text-gray-400">+{{ $stats['new_users_today'] }} today</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
            <p class="text-sm text-gray-500">Active Subscribers</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['subscribers']) }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
            <p class="text-sm text-gray-500">Total QR Codes</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_qr_codes']) }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
            <p class="text-sm text-gray-500">Total Scans</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['total_scans']) }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
            <p class="text-sm text-gray-500">Scans Today</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($stats['scans_today']) }}</p>
        </div>
    </div>
    @endif

    @if($tab === 'users')
    {{-- User management --}}
    <div class="mt-6">
        <input wire:model.live.debounce.300ms="userSearch" type="text" placeholder="Search users by name or email..."
               class="block w-full max-w-md rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
    </div>

    <div class="mt-4 overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">User</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">QR Codes</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Credits</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Role</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Joined</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $user->email }}</p>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ $user->qr_codes_count }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">{{ number_format($user->creditBalance?->balance ?? 0) }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $user->is_admin ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $user->is_admin ? 'Admin' : 'User' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            @if($user->id !== auth()->id())
                                <button wire:click="toggleAdmin({{ $user->id }})" class="text-xs text-primary-600 hover:text-primary-800">
                                    {{ $user->is_admin ? 'Remove Admin' : 'Make Admin' }}
                                </button>
                                <button wire:click="deleteUser({{ $user->id }})" wire:confirm="Are you sure you want to delete this user?" class="ml-3 text-xs text-red-600 hover:text-red-800">
                                    {{ __('common.delete') }}
                                </button>
                            @else
                                <span class="text-xs text-gray-400">You</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
    @endif
</div>
