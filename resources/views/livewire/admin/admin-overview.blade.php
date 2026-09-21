@php use App\Support\Money; @endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('nav.admin_panel') }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ config('app.name') }} · {{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    <x-admin.nav current="overview" />

    {{-- Headline numbers --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat :label="__('admin.total_users')"
                      :value="number_format($stats['total_users'])"
                      :hint="'+' . number_format($stats['new_users_today']) . ' ' . __('admin.today') . ' · +' . number_format($stats['new_users_30d']) . ' / 30d'"
                      icon="fa-solid fa-users"
                      :href="route('admin.users')" />

        <x-admin.stat :label="__('admin.mrr')"
                      :value="Money::compact($stats['mrr_cents'])"
                      :hint="__('admin.arr') . ' ' . Money::compact($stats['arr_cents']) . ($stats['comped_subscriptions'] > 0 ? ' · ' . __('admin.comped_excluded', ['count' => $stats['comped_subscriptions']]) : '')"
                      icon="fa-solid fa-arrows-rotate"
                      tone="positive"
                      :href="route('admin.revenue')" />

        <x-admin.stat :label="__('admin.active_subscribers')"
                      :value="number_format($stats['paid_users'])"
                      :hint="__('admin.conversion_rate') . ' ' . $stats['conversion_rate'] . '% · ' . number_format($stats['trialing_users']) . ' ' . __('admin.trialing')"
                      icon="fa-solid fa-credit-card" />

        <x-admin.stat :label="__('admin.arpu')"
                      :value="Money::format($stats['arpu_cents'])"
                      :hint="__('admin.arpu_hint')"
                      icon="fa-solid fa-user-tag" />

        <x-admin.stat :label="__('admin.total_qr_codes')"
                      :value="number_format($stats['total_qr_codes'])"
                      :hint="'+' . number_format($stats['qr_codes_today']) . ' ' . __('admin.today')"
                      icon="fa-solid fa-qrcode"
                      :href="route('admin.usage')" />

        <x-admin.stat :label="__('admin.total_scans')"
                      :value="number_format($stats['total_scans'])"
                      :hint="number_format($stats['scans_30d']) . ' / 30d'"
                      icon="fa-solid fa-chart-line"
                      :href="route('admin.usage')" />

        <x-admin.stat :label="__('admin.scans_today')"
                      :value="number_format($stats['scans_today'])"
                      icon="fa-solid fa-bolt" />

        <x-admin.stat :label="__('admin.one_off_revenue')"
                      :value="Money::compact($stats['one_off_30d_cents'])"
                      :hint="__('admin.last_30_days')"
                      icon="fa-solid fa-receipt"
                      :href="route('admin.revenue')" />
    </div>

    {{-- Growth --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <x-admin.panel :title="__('admin.signups_chart')">
            <x-admin.bar-chart :series="$signups" :label="__('admin.signups_chart')" color="bg-primary-500" />
        </x-admin.panel>

        <x-admin.panel :title="__('admin.scans_chart')">
            <x-admin.bar-chart :series="$scans" :label="__('admin.scans_chart')" color="bg-emerald-500" />
        </x-admin.panel>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Plan mix --}}
        <x-admin.panel :title="__('admin.plan_distribution')">
            <div class="space-y-4">
                @foreach($planDistribution as $row)
                    <div>
                        <div class="flex items-baseline justify-between text-sm">
                            <a href="{{ route('admin.users', ['plan' => $row['tier']->value]) }}" wire:navigate
                               class="font-medium text-gray-700 hover:text-primary-600 dark:text-gray-200 dark:hover:text-primary-400">
                                {{ $row['tier']->label() }}
                            </a>
                            <span class="tabular-nums text-gray-900 dark:text-gray-100">
                                {{ number_format($row['users']) }}
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $row['share'] }}%</span>
                            </span>
                        </div>
                        <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-zinc-800">
                            <div @class([
                                    'h-2 rounded-full',
                                    'bg-gray-400 dark:bg-zinc-500' => $row['tier']->value === 'starter',
                                    'bg-primary-500' => $row['tier']->value === 'pro',
                                    'bg-purple-500' => $row['tier']->value === 'enterprise',
                                 ]) style="width: {{ $row['share'] }}%"></div>
                        </div>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                            {{ $row['tier']->maxDynamicQrCodes() === null ? __('admin.unlimited') : $row['tier']->maxDynamicQrCodes() }}
                            {{ __('admin.dynamic_qr_codes') }}
                            @if($row['tier']->priceMonthly() > 0)
                                · {{ Money::format($row['tier']->priceMonthly(), false) }}/mo
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        </x-admin.panel>

        {{-- Things an admin should act on --}}
        <x-admin.panel :title="__('admin.needs_attention')">
            <ul class="divide-y divide-gray-100 text-sm dark:divide-zinc-800">
                <li class="flex items-center justify-between py-2">
                    <a href="{{ route('admin.users', ['flag' => 'at_limit']) }}" wire:navigate class="text-gray-600 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400">
                        {{ __('admin.at_plan_limit') }}
                    </a>
                    <span @class(['font-semibold tabular-nums', 'text-amber-600 dark:text-amber-400' => $attention['at_limit'] > 0, 'text-gray-400 dark:text-gray-500' => $attention['at_limit'] === 0])>
                        {{ number_format($attention['at_limit']) }}
                    </span>
                </li>
                <li class="flex items-center justify-between py-2">
                    <a href="{{ route('admin.users', ['flag' => 'unverified']) }}" wire:navigate class="text-gray-600 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400">
                        {{ __('admin.unverified_users') }}
                    </a>
                    <span class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($attention['unverified']) }}</span>
                </li>
                <li class="flex items-center justify-between py-2">
                    <a href="{{ route('admin.revenue', ['actionStatus' => 'pending']) }}" wire:navigate class="text-gray-600 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400">
                        {{ __('admin.pending_paid_actions') }}
                    </a>
                    <span class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($attention['pending_paid_actions']) }}</span>
                </li>
                <li class="flex items-center justify-between py-2">
                    <a href="{{ route('admin.users', ['flag' => 'deletion']) }}" wire:navigate class="text-gray-600 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400">
                        {{ __('admin.scheduled_deletions') }}
                    </a>
                    <span @class(['font-semibold tabular-nums', 'text-red-600 dark:text-red-400' => $attention['scheduled_deletions'] > 0, 'text-gray-400 dark:text-gray-500' => $attention['scheduled_deletions'] === 0])>
                        {{ number_format($attention['scheduled_deletions']) }}
                    </span>
                </li>
            </ul>
        </x-admin.panel>

        {{-- Recent money --}}
        <x-admin.panel :title="__('admin.recent_payments')">
            <ul class="divide-y divide-gray-100 text-sm dark:divide-zinc-800">
                @forelse($recentPayments as $payment)
                    <li class="flex items-center justify-between gap-3 py-2">
                        <div class="min-w-0">
                            @if($payment->user)
                                <a href="{{ route('admin.users.show', $payment->user) }}" wire:navigate class="block truncate font-medium text-gray-900 hover:text-primary-600 dark:text-gray-100 dark:hover:text-primary-400">
                                    {{ $payment->user->name }}
                                </a>
                            @else
                                <span class="block truncate text-gray-400 dark:text-gray-500">—</span>
                            @endif
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ str_replace('_', ' ', $payment->action_type) }} ·
                                {{ optional($payment->paid_at)->diffForHumans() }}
                            </p>
                        </div>
                        <span class="shrink-0 font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
                            {{ Money::format($payment->amount_cents) }}
                        </span>
                    </li>
                @empty
                    <li class="py-2 text-xs text-gray-400 dark:text-gray-500">{{ __('admin.no_data') }}</li>
                @endforelse
            </ul>
        </x-admin.panel>
    </div>

    {{-- Newest accounts --}}
    <x-admin.panel :title="__('admin.recent_signups')" padding="p-0">
        <x-slot:actions>
            <a href="{{ route('admin.users') }}" wire:navigate class="text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                {{ __('admin.users') }} →
            </a>
        </x-slot:actions>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
                <thead class="bg-gray-50 dark:bg-zinc-900/60">
                    <tr>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_user') }}</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_plan') }}</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.total_qr_codes') }}</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_joined') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                    @forelse($recentUsers as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.users.show', $row['user']) }}" wire:navigate class="block">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row['user']->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['user']->email }}</p>
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                <x-admin.badge :tone="$row['tier']->value === 'enterprise' ? 'purple' : ($row['tier']->value === 'pro' ? 'blue' : 'gray')">
                                    {{ $row['tier']->label() }}
                                </x-admin.badge>
                            </td>
                            <td class="px-5 py-3 text-sm tabular-nums text-gray-600 dark:text-gray-300">{{ number_format($row['qr_codes']) }}</td>
                            <td class="px-5 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $row['user']->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-6 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('admin.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.panel>
</div>
