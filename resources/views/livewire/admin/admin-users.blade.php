@php use App\Support\Money; @endphp

@php
    $columns = [
        ['key' => 'name', 'label' => __('admin.column_user'), 'align' => 'left'],
        ['key' => null, 'label' => __('admin.column_plan'), 'align' => 'left'],
        ['key' => null, 'label' => __('admin.column_dynamic_usage'), 'align' => 'left'],
        ['key' => null, 'label' => __('admin.column_static_usage'), 'align' => 'left'],
        ['key' => 'scans_total', 'label' => __('admin.column_scans'), 'align' => 'right'],
        ['key' => 'paid_cents', 'label' => __('admin.column_paid'), 'align' => 'right'],
        ['key' => 'last_qr_at', 'label' => __('admin.column_last_activity'), 'align' => 'left'],
        ['key' => 'created_at', 'label' => __('admin.column_joined'), 'align' => 'left'],
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('admin.users') }}</h1>
        <button type="button" wire:click="exportCsv"
                class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-200 transition hover:bg-gray-50 dark:bg-zinc-900 dark:text-gray-200 dark:ring-zinc-700 dark:hover:bg-zinc-800">
            <i class="fa-solid fa-file-csv text-xs" aria-hidden="true"></i>
            {{ __('admin.export_csv') }}
        </button>
    </div>

    <x-admin.nav current="users" />

    {{-- Filters --}}
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-gray-400" aria-hidden="true"></i>
            <input wire:model.live.debounce.300ms="search" type="search"
                   placeholder="{{ __('admin.search_users') }}"
                   class="block w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
        </div>

        <select wire:model.live="plan" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="all">{{ __('admin.all_plans') }}</option>
            @foreach($planOptions as $tier)
                <option value="{{ $tier->value }}">{{ $tier->label() }}</option>
            @endforeach
        </select>

        <select wire:model.live="status" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="all">{{ __('admin.all_statuses') }}</option>
            <option value="active">{{ __('admin.status_active') }}</option>
            <option value="trialing">{{ __('admin.status_trialing') }}</option>
            <option value="grace">{{ __('admin.status_grace') }}</option>
            <option value="canceled">{{ __('admin.status_canceled') }}</option>
            <option value="free">{{ __('admin.status_free') }}</option>
        </select>

        <select wire:model.live="flag" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="all">{{ __('admin.all_users') }}</option>
            <option value="admins">{{ __('admin.flag_admins') }}</option>
            <option value="unverified">{{ __('admin.flag_unverified') }}</option>
            <option value="at_limit">{{ __('admin.flag_at_limit') }}</option>
            <option value="deletion">{{ __('admin.flag_deletion') }}</option>
        </select>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500 dark:text-gray-400">
        <p>{{ __('admin.users_found', ['count' => number_format($users->total())]) }}</p>
        @if($search !== '' || $plan !== 'all' || $status !== 'all' || $flag !== 'all')
            <button type="button" wire:click="resetFilters" class="text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                {{ __('common.cancel') }} {{ strtolower(__('common.filter')) }}
            </button>
        @endif
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5 dark:bg-zinc-900 dark:ring-zinc-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
            <thead class="bg-gray-50 dark:bg-zinc-900/60">
                <tr>
                    @foreach($columns as $column)
                        <th class="px-4 py-3 text-{{ $column['align'] }} text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            @if($column['key'])
                                <button type="button" wire:click="sortBy('{{ $column['key'] }}')" class="inline-flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200">
                                    {{ $column['label'] }}
                                    @if($sort === $column['key'])
                                        <i class="fa-solid fa-caret-{{ $direction === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                                    @endif
                                </button>
                            @else
                                {{ $column['label'] }}
                            @endif
                        </th>
                    @endforeach
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                @forelse($rows as $row)
                    @php
                        $user = $row['user'];
                        $limits = $row['limits'];
                        // Full class strings: Tailwind only ships classes it can see.
                        $stateClass = match ($row['state']) {
                            'active' => 'text-emerald-600 dark:text-emerald-400',
                            'trialing' => 'text-primary-600 dark:text-primary-400',
                            'grace' => 'text-amber-600 dark:text-amber-400',
                            'canceled' => 'text-red-600 dark:text-red-400',
                            default => 'text-gray-500 dark:text-gray-400',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50" wire:key="user-{{ $user->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.users.show', $user) }}" wire:navigate class="flex items-center gap-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700 dark:bg-primary-950 dark:text-primary-400">
                                    {{ mb_substr($user->name, 0, 1) }}
                                </span>
                                <span class="min-w-0">
                                    <span class="flex items-center gap-1.5">
                                        <span class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</span>
                                        @if($user->is_admin)
                                            <i class="fa-solid fa-shield-halved text-[10px] text-purple-500" title="{{ __('admin.role_admin') }}" aria-hidden="true"></i>
                                        @endif
                                        @if(! $user->email_verified_at)
                                            <i class="fa-solid fa-envelope-circle-check text-[10px] text-amber-500" title="{{ __('admin.not_verified') }}" aria-hidden="true"></i>
                                        @endif
                                        @if($user->account_deletion_scheduled_at)
                                            <i class="fa-solid fa-triangle-exclamation text-[10px] text-red-500" title="{{ __('admin.flag_deletion') }}" aria-hidden="true"></i>
                                        @endif
                                    </span>
                                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</span>
                                </span>
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col items-start gap-1">
                                <x-admin.badge :tone="$limits['tier']->value === 'enterprise' ? 'purple' : ($limits['tier']->value === 'pro' ? 'blue' : 'gray')">
                                    {{ $limits['tier']->label() }}
                                </x-admin.badge>
                                <span class="text-[11px] {{ $stateClass }}">
                                    {{ __('admin.status_' . $row['state']) }}@if($row['comped']) · {{ __('admin.comped') }}@endif
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <x-admin.meter :used="$limits['dynamic_used']" :limit="$limits['dynamic_limit']" compact />
                        </td>
                        <td class="px-4 py-3">
                            <x-admin.meter :used="$limits['static_used']" :limit="$limits['static_limit']" compact />
                        </td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums text-gray-700 dark:text-gray-300">{{ number_format((int) $user->scans_total) }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums {{ (int) $user->paid_cents > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}">
                            {{ Money::format((int) $user->paid_cents) }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                            {{ $user->last_qr_at ? \Illuminate\Support\Carbon::parse($user->last_qr_at)->diffForHumans() : __('admin.never') }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $user->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($user->is(auth()->user()))
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('admin.you') }}</span>
                            @else
                                <div x-data="{ ...exclusiveDropdownMixin('user-actions-{{ $user->id }}'), init() { this._dropdownInit(); } }"
                                     @click.outside="closeDropdown()" class="relative inline-block text-left">
                                    <button type="button" @click.stop="toggleDropdown()"
                                            class="rounded-lg px-2 py-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-zinc-800 dark:hover:text-gray-200"
                                            aria-label="{{ __('common.actions') }}">
                                        <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
                                    </button>
                                    <div x-show="open" x-cloak x-transition
                                         class="absolute right-0 z-20 mt-1 w-52 rounded-lg bg-white py-1 text-left shadow-lg ring-1 ring-gray-900/5 dark:bg-zinc-900 dark:ring-zinc-700">
                                        <a href="{{ route('admin.users.show', $user) }}" wire:navigate
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-zinc-800">
                                            <i class="fa-solid fa-user w-4 text-xs" aria-hidden="true"></i> {{ __('common.edit') }}
                                        </a>
                                        <button type="button" wire:click="impersonate({{ $user->id }})"
                                                wire:confirm="{{ __('admin.impersonate_confirm') }}"
                                                class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-zinc-800">
                                            <i class="fa-solid fa-user-secret w-4 text-xs" aria-hidden="true"></i> {{ __('admin.impersonate') }}
                                        </button>
                                        <button type="button" wire:click="toggleAdmin({{ $user->id }})"
                                                class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-zinc-800">
                                            <i class="fa-solid fa-shield-halved w-4 text-xs" aria-hidden="true"></i>
                                            {{ $user->is_admin ? __('admin.remove_admin') : __('admin.make_admin') }}
                                        </button>
                                        <div class="my-1 border-t border-gray-100 dark:border-zinc-800"></div>
                                        <button type="button" wire:click="deleteUser({{ $user->id }})"
                                                wire:confirm="{{ __('admin.delete_user_confirm') }}"
                                                class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40">
                                            <i class="fa-solid fa-trash w-4 text-xs" aria-hidden="true"></i> {{ __('admin.delete_user') }}
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('common.no_results') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $users->links() }}</div>
</div>
