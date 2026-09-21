@php use App\Support\Money; @endphp

<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('admin.revenue') }}</h1>

    <x-admin.nav current="revenue" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat :label="__('admin.mrr')" :value="Money::compact($stats['mrr_cents'])" :hint="__('admin.mrr_hint')" icon="fa-solid fa-arrows-rotate" tone="positive" />
        <x-admin.stat :label="__('admin.arr')" :value="Money::compact($stats['arr_cents'])" icon="fa-solid fa-calendar-check" />
        <x-admin.stat :label="__('admin.active_subscriptions')" :value="number_format($stats['active_subscriptions'])" :hint="number_format($stats['trialing']) . ' ' . __('admin.trialing')" icon="fa-solid fa-credit-card" />
        <x-admin.stat :label="__('admin.canceled_30d')" :value="number_format($stats['canceled_30d'])" icon="fa-solid fa-user-minus" :tone="$stats['canceled_30d'] > 0 ? 'warning' : 'default'" />
        <x-admin.stat :label="__('admin.one_off_revenue')" :value="Money::compact($stats['one_off_total_cents'])" :hint="__('admin.all_time')" icon="fa-solid fa-receipt" />
        <x-admin.stat :label="__('admin.one_off_revenue')" :value="Money::compact($stats['one_off_30d_cents'])" :hint="__('admin.last_30_days')" icon="fa-solid fa-calendar-days" />
        <x-admin.stat :label="__('admin.comped_subscriptions')" :value="number_format($stats['comped_subscriptions'])" :hint="__('admin.comped_hint')" icon="fa-solid fa-gift" />
        <x-admin.stat :label="__('admin.pending_paid_actions')" :value="number_format($stats['one_off_pending'])" icon="fa-solid fa-hourglass-half" :tone="$stats['one_off_pending'] > 0 ? 'warning' : 'default'" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-admin.panel :title="__('admin.mrr_by_plan')">
            <x-admin.breakdown :rows="collect($mrrByTier)->map(fn ($row) => ['label' => $row['tier']->label() . ' (' . $row['users'] . ')', 'value' => $row['mrr_cents']])"
                               money color="bg-primary-500" />
        </x-admin.panel>

        <x-admin.panel :title="__('admin.new_subscriptions_chart')" class="lg:col-span-2">
            <x-admin.bar-chart :series="$newSubscriptions" :label="__('admin.new_subscriptions_chart')" color="bg-primary-500" :label-every="1" />
        </x-admin.panel>
    </div>

    <x-admin.panel :title="__('admin.one_off_revenue_chart')">
        <x-admin.bar-chart :series="$oneOffRevenue" :label="__('admin.one_off_revenue_chart')" money color="bg-emerald-500" :label-every="1" />
    </x-admin.panel>

    {{-- Subscriptions --}}
    <x-admin.panel :title="__('admin.subscription_list')" padding="p-0">
        <x-slot:actions>
            <select wire:model.live="subscriptionStatus" class="rounded-lg border-gray-300 py-1.5 text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="all">{{ __('admin.all_statuses') }}</option>
                <option value="active">{{ __('admin.status_active') }}</option>
                <option value="trialing">{{ __('admin.status_trialing') }}</option>
                <option value="canceled">{{ __('admin.status_canceled') }}</option>
            </select>
        </x-slot:actions>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-zinc-800">
                <thead class="bg-gray-50 dark:bg-zinc-900/60">
                    <tr>
                        <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_user') }}</th>
                        <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_plan') }}</th>
                        <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('common.status') }}</th>
                        <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_billing') }}</th>
                        <th class="px-5 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.mrr') }}</th>
                        <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_started') }}</th>
                        <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_ends') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                    @forelse($subscriptionRows as $row)
                        @php $subscription = $row['subscription']; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50" wire:key="revenue-sub-{{ $subscription->id }}">
                            <td class="px-5 py-2.5">
                                @if($subscription->user)
                                    <a href="{{ route('admin.users.show', $subscription->user) }}" wire:navigate class="block">
                                        <span class="block font-medium text-gray-900 dark:text-gray-100">{{ $subscription->user->name }}</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $subscription->user->email }}</span>
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5">
                                <x-admin.badge :tone="$row['tier']->value === 'enterprise' ? 'purple' : ($row['tier']->value === 'pro' ? 'blue' : 'gray')">
                                    {{ $row['tier']->label() }}
                                </x-admin.badge>
                                @if($row['comped'])
                                    <x-admin.badge tone="amber" class="ml-1">{{ __('admin.comped') }}</x-admin.badge>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-gray-600 dark:text-gray-300">{{ __('admin.status_' . $row['state']) }}</td>
                            <td class="px-5 py-2.5 text-gray-600 dark:text-gray-300">
                                {{ $row['interval'] ? __('admin.billing_' . $row['interval']) : __('admin.billing_unknown') }}
                            </td>
                            <td class="px-5 py-2.5 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ Money::format($row['monthly_cents']) }}</td>
                            <td class="px-5 py-2.5 text-xs text-gray-500 dark:text-gray-400">{{ $subscription->created_at->format('M j, Y') }}</td>
                            <td class="px-5 py-2.5 text-xs text-gray-500 dark:text-gray-400">{{ $subscription->ends_at?->format('M j, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('admin.no_subscriptions_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($subscriptions->hasPages())
            <div class="border-t border-gray-100 px-5 py-3 dark:border-zinc-800">{{ $subscriptions->links() }}</div>
        @endif
    </x-admin.panel>

    {{-- One-off charges --}}
    <x-admin.panel :title="__('admin.one_off_actions')" padding="p-0">
        <x-slot:actions>
            <select wire:model.live="actionStatus" class="rounded-lg border-gray-300 py-1.5 text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="all">{{ __('admin.all_statuses') }}</option>
                <option value="completed">{{ __('admin.status_completed') }}</option>
                <option value="pending">{{ __('admin.status_pending') }}</option>
                <option value="failed">{{ __('admin.status_failed') }}</option>
            </select>
        </x-slot:actions>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-zinc-800">
                <thead class="bg-gray-50 dark:bg-zinc-900/60">
                    <tr>
                        <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_user') }}</th>
                        <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_action_type') }}</th>
                        <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('common.status') }}</th>
                        <th class="px-5 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_amount') }}</th>
                        <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_paid_at') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                    @forelse($paidActions as $action)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50" wire:key="revenue-action-{{ $action->id }}">
                            <td class="px-5 py-2.5">
                                @if($action->user)
                                    <a href="{{ route('admin.users.show', $action->user) }}" wire:navigate class="block">
                                        <span class="block font-medium text-gray-900 dark:text-gray-100">{{ $action->user->name }}</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $action->user->email }}</span>
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 capitalize text-gray-700 dark:text-gray-200">{{ str_replace('_', ' ', $action->action_type) }}</td>
                            <td class="px-5 py-2.5">
                                <x-admin.badge :tone="$action->status === 'completed' ? 'green' : ($action->status === 'pending' ? 'amber' : 'red')">
                                    {{ __('admin.status_' . $action->status) }}
                                </x-admin.badge>
                            </td>
                            <td class="px-5 py-2.5 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ Money::format($action->amount_cents) }}</td>
                            <td class="px-5 py-2.5 text-xs text-gray-500 dark:text-gray-400">{{ $action->paid_at?->format('M j, Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('admin.no_paid_actions_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($paidActions->hasPages())
            <div class="border-t border-gray-100 px-5 py-3 dark:border-zinc-800">{{ $paidActions->links() }}</div>
        @endif
    </x-admin.panel>
</div>
