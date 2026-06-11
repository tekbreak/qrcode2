<div class="space-y-6">
    @if(!$team)
        {{-- Create team --}}
        <div class="rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm ring-1 ring-gray-900/5 dark:ring-zinc-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Create a Team</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Teams let you collaborate on QR codes with others.</p>
            <form wire:submit="createTeam" class="mt-4 flex gap-3">
                <input wire:model="teamName" type="text" placeholder="Team name"
                       class="flex-1 rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">
                    {{ __('common.create') }}
                </button>
            </form>
            @error('teamName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    @else
        {{-- Team info --}}
        <div class="rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm ring-1 ring-gray-900/5 dark:ring-zinc-800">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $team->name }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ $members->count() }} members</p>
                </div>
            </div>
        </div>

        {{-- Invite --}}
        <div class="rounded-xl bg-white dark:bg-zinc-900 p-6 shadow-sm ring-1 ring-gray-900/5 dark:ring-zinc-800">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Invite Member</h3>
            <form wire:submit="inviteMember" class="mt-3 flex gap-3">
                <input wire:model="inviteEmail" type="email" placeholder="colleague@example.com"
                       class="flex-1 rounded-lg border-gray-300 dark:border-zinc-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">
                    Invite
                </button>
            </form>
            @error('inviteEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Members list --}}
        <div class="rounded-xl bg-white dark:bg-zinc-900 shadow-sm ring-1 ring-gray-900/5 dark:ring-zinc-800 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
                <thead class="bg-gray-50 dark:bg-zinc-800/60">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 dark:text-gray-500">Member</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 dark:text-gray-500">Role</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-800">
                    @foreach($members as $member)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $member->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ $member->email }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if($member->id === $team->owner_id)
                                    <span class="inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">Owner</span>
                                @else
                                    <select wire:change="updateRole({{ $member->id }}, $event.target.value)"
                                            class="rounded-lg border-gray-300 dark:border-zinc-700 text-xs">
                                        <option value="admin" {{ $member->pivot->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="member" {{ $member->pivot->role === 'member' ? 'selected' : '' }}>Member</option>
                                    </select>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @if($member->id !== $team->owner_id && auth()->id() === $team->owner_id)
                                    <button wire:click="removeMember({{ $member->id }})" wire:confirm="Remove this member?"
                                            class="text-xs text-red-600 hover:text-red-800">Remove</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
