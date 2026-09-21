@php use App\Support\Money; @endphp

<div class="space-y-6">
    <a href="{{ route('admin.users') }}" wire:navigate class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
        <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i> {{ __('admin.back_to_users') }}
    </a>

    {{-- Identity + primary actions --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary-100 text-xl font-bold text-primary-700 dark:bg-primary-950 dark:text-primary-400">
                {{ mb_substr($user->name, 0, 1) }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $user->name }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <x-admin.badge :tone="$limits['tier']->value === 'enterprise' ? 'purple' : ($limits['tier']->value === 'pro' ? 'blue' : 'gray')">
                        {{ $limits['tier']->label() }}
                    </x-admin.badge>
                    <x-admin.badge :tone="match($subscriptionState) { 'active' => 'green', 'trialing' => 'blue', 'grace' => 'amber', 'canceled' => 'red', default => 'gray' }">
                        {{ __('admin.status_' . $subscriptionState) }}
                    </x-admin.badge>
                    @if($user->is_admin)
                        <x-admin.badge tone="purple"><i class="fa-solid fa-shield-halved text-[10px]" aria-hidden="true"></i> {{ __('admin.role_admin') }}</x-admin.badge>
                    @endif
                    @if(! $user->email_verified_at)
                        <x-admin.badge tone="amber">{{ __('admin.not_verified') }}</x-admin.badge>
                    @endif
                    @if($limits['at_limit'])
                        <x-admin.badge tone="amber">{{ __('admin.flag_at_limit') }}</x-admin.badge>
                    @endif
                    @if($deletionDueAt)
                        <x-admin.badge tone="red">{{ __('admin.deletion_scheduled_for', ['date' => $deletionDueAt->format('M j, Y')]) }}</x-admin.badge>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @unless($user->is(auth()->user()))
                <button type="button" wire:click="impersonate({{ $user->id }})" wire:confirm="{{ __('admin.impersonate_confirm') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700">
                    <i class="fa-solid fa-user-secret text-xs" aria-hidden="true"></i> {{ __('admin.impersonate') }}
                </button>
                <button type="button" wire:click="toggleUserAdmin"
                        class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-200 transition hover:bg-gray-50 dark:bg-zinc-900 dark:text-gray-200 dark:ring-zinc-700 dark:hover:bg-zinc-800">
                    <i class="fa-solid fa-shield-halved text-xs" aria-hidden="true"></i>
                    {{ $user->is_admin ? __('admin.remove_admin') : __('admin.make_admin') }}
                </button>
            @endunless
            @if($stripeUrl)
                <a href="{{ $stripeUrl }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-gray-200 transition hover:bg-gray-50 dark:bg-zinc-900 dark:text-gray-200 dark:ring-zinc-700 dark:hover:bg-zinc-800">
                    <i class="fa-brands fa-stripe-s text-xs" aria-hidden="true"></i> {{ __('admin.open_in_stripe') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Headline numbers for this account --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat :label="__('admin.lifetime_value')" :value="Money::format($lifetimeCents)" :hint="__('admin.lifetime_value_hint')" icon="fa-solid fa-sack-dollar" tone="positive" />
        <x-admin.stat :label="__('admin.total_scans')" :value="number_format($scanTotals['total'])" :hint="number_format($scanTotals['last_30d']) . ' / 30d · ' . number_format($scanTotals['unique']) . ' ' . __('admin.unique_scans')" icon="fa-solid fa-chart-line" />
        <x-admin.stat :label="__('admin.qr_codes_of_user')" :value="number_format($user->qr_total_count)" :hint="number_format($limits['dynamic_used']) . ' ' . __('admin.dynamic') . ' · ' . number_format($limits['static_used']) . ' ' . __('admin.static')" icon="fa-solid fa-qrcode" />
        <x-admin.stat :label="__('admin.joined_on')" :value="$user->created_at->format('M j, Y')" :hint="$user->created_at->diffForHumans()" icon="fa-solid fa-calendar-day" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Plan, limits and the controls that change them --}}
            <x-admin.panel :title="__('admin.plan_and_limits')">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.meter :label="__('admin.dynamic_qr_codes')" :used="$limits['dynamic_used']" :limit="$limits['dynamic_limit']" />
                    <x-admin.meter :label="__('admin.static_qr_codes')" :used="$limits['static_used']" :limit="$limits['static_limit']" />
                </div>

                <div class="mt-5 border-t border-gray-100 pt-4 dark:border-zinc-800">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('admin.features_included') }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach($limits['tier']->features() as $feature)
                            <x-admin.badge tone="gray">
                                <i class="{{ $feature->icon() }} text-[10px]" aria-hidden="true"></i> {{ $feature->label() }}
                            </x-admin.badge>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-end gap-3 border-t border-gray-100 pt-4 dark:border-zinc-800">
                    <label class="block">
                        <span class="block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('admin.change_plan') }}</span>
                        <select wire:model.live="newPlan" class="mt-1 block rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @foreach($planOptions as $tier)
                                <option value="{{ $tier->value }}">{{ $tier->label() }}</option>
                            @endforeach
                        </select>
                    </label>

                    @php $selectedTier = \App\Enums\PlanTier::tryFrom($newPlan); @endphp
                    @if($selectedTier && $selectedTier->hasYearlyBilling())
                        <label class="flex items-center gap-2 pb-2 text-sm text-gray-600 dark:text-gray-300">
                            <input type="checkbox" wire:model.live="newPlanYearly" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            {{ __('admin.billing_yearly') }}
                        </label>
                    @endif

                    <button type="button" wire:click="applyPlanChange" wire:confirm="{{ __('admin.change_plan_confirm') }}"
                            class="rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-primary-700">
                        {{ __('common.save') }}
                    </button>

                    @if($subscription)
                        <button type="button" wire:click="cancelUserSubscription" wire:confirm="{{ __('admin.cancel_subscription_confirm') }}"
                                class="rounded-lg px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40">
                            {{ __('admin.cancel_subscription') }}
                        </button>
                    @endif
                </div>
                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">{{ __('admin.refund_hint') }}</p>
            </x-admin.panel>

            {{-- Money --}}
            <x-admin.panel :title="__('admin.payments')" padding="p-0">
                <div class="px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('admin.subscriptions') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-zinc-800">
                        <thead class="bg-gray-50 dark:bg-zinc-900/60">
                            <tr>
                                <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_plan') }}</th>
                                <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('common.status') }}</th>
                                <th class="px-5 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_price') }}</th>
                                <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_started') }}</th>
                                <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_ends') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @forelse($subscriptionRows as $row)
                                <tr wire:key="sub-{{ $row['subscription']->id }}">
                                    <td class="px-5 py-2.5">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $row['tier']->label() }}</span>
                                        @if($row['comped'])
                                            <x-admin.badge tone="amber" class="ml-1" :title="__('admin.comped_hint')">{{ __('admin.comped') }}</x-admin.badge>
                                        @endif
                                    </td>
                                    <td class="px-5 py-2.5 text-gray-600 dark:text-gray-300">{{ __('admin.status_' . $row['state']) }}</td>
                                    <td class="px-5 py-2.5 text-right tabular-nums text-gray-900 dark:text-gray-100">
                                        {{ $row['monthly_cents'] > 0 ? Money::format($row['monthly_cents']) . '/mo' : '—' }}
                                        @if($row['interval'])
                                            <span class="block text-xs text-gray-400 dark:text-gray-500">{{ __('admin.billing_' . $row['interval']) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-2.5 text-xs text-gray-500 dark:text-gray-400">{{ $row['subscription']->created_at->format('M j, Y') }}</td>
                                    <td class="px-5 py-2.5 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $row['subscription']->ends_at?->format('M j, Y') ?? '—' }}
                                        @if($row['subscription']->trial_ends_at)
                                            <span class="block">{{ __('admin.column_trial_ends') }}: {{ $row['subscription']->trial_ends_at->format('M j, Y') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-6 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('admin.no_subscriptions') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-5 py-4 dark:border-zinc-800">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('admin.one_off_actions') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-zinc-800">
                        <thead class="bg-gray-50 dark:bg-zinc-900/60">
                            <tr>
                                <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_action_type') }}</th>
                                <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('common.status') }}</th>
                                <th class="px-5 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_amount') }}</th>
                                <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_paid_at') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @forelse($paidActions as $action)
                                <tr wire:key="action-{{ $action->id }}">
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
                                <tr><td colspan="4" class="px-5 py-6 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('admin.no_paid_actions') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-admin.panel>

            {{-- What they actually do with the product --}}
            <x-admin.panel :title="__('admin.scans_30d')">
                <x-admin.bar-chart :series="$dailyScans" :label="__('admin.scans_30d')" color="bg-emerald-500" />
            </x-admin.panel>

            <x-admin.panel :title="__('admin.qr_codes_of_user')" padding="p-0">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-zinc-800">
                        <thead class="bg-gray-50 dark:bg-zinc-900/60">
                            <tr>
                                <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_name') }}</th>
                                <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_type') }}</th>
                                <th class="px-5 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_scans') }}</th>
                                <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('common.status') }}</th>
                                <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_joined') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @forelse($qrCodes as $qr)
                                <tr wire:key="qr-{{ $qr->id }}">
                                    <td class="px-5 py-2.5">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $qr->name }}</p>
                                        @if($qr->shortLink)
                                            <p class="truncate text-xs text-gray-400 dark:text-gray-500">/{{ $qr->shortLink->slug }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-2.5">
                                        <x-admin.badge :tone="$qr->is_dynamic ? 'blue' : 'gray'">
                                            {{ $qr->type->value }} · {{ $qr->is_dynamic ? __('admin.dynamic') : __('admin.static') }}
                                        </x-admin.badge>
                                    </td>
                                    <td class="px-5 py-2.5 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ number_format($qr->total_scans) }}</td>
                                    <td class="px-5 py-2.5 text-xs capitalize text-gray-500 dark:text-gray-400">{{ $qr->status }}</td>
                                    <td class="px-5 py-2.5 text-xs text-gray-500 dark:text-gray-400">{{ $qr->created_at->format('M j, Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-6 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('admin.no_qr_codes') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($qrCodes->hasPages())
                    <div class="border-t border-gray-100 px-5 py-3 dark:border-zinc-800">{{ $qrCodes->links() }}</div>
                @endif
            </x-admin.panel>
        </div>

        {{-- Account facts + danger zone --}}
        <div class="space-y-6">
            <x-admin.panel :title="__('admin.account')">
                <dl class="space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.joined_on') }}</dt>
                        <dd class="text-right text-gray-900 dark:text-gray-100">{{ $user->created_at->format('M j, Y') }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.email_verified') }}</dt>
                        <dd class="text-right {{ $user->email_verified_at ? 'text-gray-900 dark:text-gray-100' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ $user->email_verified_at?->format('M j, Y') ?? __('admin.not_verified') }}
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.sign_in_method') }}</dt>
                        <dd class="text-right text-gray-900 dark:text-gray-100">{{ $user->google_id ? __('admin.sign_in_google') : __('admin.sign_in_password') }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.locale') }}</dt>
                        <dd class="text-right uppercase text-gray-900 dark:text-gray-100">{{ $user->locale }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.stripe_customer') }}</dt>
                        <dd class="text-right font-mono text-xs text-gray-900 dark:text-gray-100">{{ $user->stripe_id ?: '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.teams_owned') }}</dt>
                        <dd class="text-right text-gray-900 dark:text-gray-100">
                            @forelse($teamsOwned as $team)
                                <span class="block">{{ $team->name }} <span class="text-xs text-gray-400">({{ $team->users_count }})</span></span>
                            @empty
                                —
                            @endforelse
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.teams_member') }}</dt>
                        <dd class="text-right text-gray-900 dark:text-gray-100">
                            @forelse($teamsMember as $team)
                                <span class="block">{{ $team->name }} <span class="text-xs text-gray-400">{{ $team->pivot->role }}</span></span>
                            @empty
                                —
                            @endforelse
                        </dd>
                    </div>
                </dl>
            </x-admin.panel>

            @unless($user->is(auth()->user()))
                <x-admin.panel :title="__('admin.danger_zone')" class="ring-red-200 dark:ring-red-900/60">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.danger_zone_hint') }}</p>
                    <button type="button" wire:click="deleteUser({{ $user->id }})" wire:confirm="{{ __('admin.delete_user_confirm') }}"
                            class="mt-3 inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-red-700">
                        <i class="fa-solid fa-trash text-xs" aria-hidden="true"></i> {{ __('admin.delete_user') }}
                    </button>
                </x-admin.panel>
            @endunless
        </div>
    </div>
</div>
