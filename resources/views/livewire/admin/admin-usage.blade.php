<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('admin.usage') }}</h1>

    <x-admin.nav current="usage" />

    {{-- Platform totals --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat :label="__('admin.total_qr_codes')" :value="number_format($stats['total_qr_codes'])"
                      :hint="number_format($stats['dynamic_qr_codes']) . ' ' . __('admin.dynamic') . ' · ' . number_format($stats['static_qr_codes']) . ' ' . __('admin.static')"
                      icon="fa-solid fa-qrcode" />
        <x-admin.stat :label="__('admin.total_scans')" :value="number_format($stats['total_scans'])"
                      :hint="number_format($stats['scans_today']) . ' ' . __('admin.today') . ' · ' . number_format($stats['scans_30d']) . ' / 30d'"
                      icon="fa-solid fa-chart-line" />
        <x-admin.stat :label="__('admin.unique_rate')" :value="$stats['unique_rate'] . '%'"
                      :hint="number_format($stats['unique_scans']) . ' ' . __('admin.unique_scans')"
                      icon="fa-solid fa-fingerprint" />
        <x-admin.stat :label="__('admin.scans_per_qr')" :value="number_format($stats['scans_per_dynamic_qr'], 1)"
                      icon="fa-solid fa-divide" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-admin.panel :title="__('admin.qr_codes_created')" :subtitle="__('admin.last_30_days')">
            <x-admin.bar-chart :series="$qrCreated" :label="__('admin.qr_codes_created')" color="bg-primary-500" />
        </x-admin.panel>

        <x-admin.panel :title="__('admin.scans_chart')">
            <x-admin.bar-chart :series="$scans" :label="__('admin.scans_chart')" color="bg-emerald-500" />
        </x-admin.panel>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-admin.panel :title="__('admin.qr_by_type')">
            <x-admin.breakdown :rows="$byType->map(fn ($row) => ['label' => ucfirst(str_replace('_', ' ', $row->type instanceof \App\Enums\QrCodeType ? $row->type->value : $row->type)), 'value' => (int) $row->count])" />
        </x-admin.panel>

        <x-admin.panel :title="__('admin.qr_by_status')">
            <x-admin.breakdown :rows="$byStatus->map(fn ($row) => ['label' => ucfirst($row->status), 'value' => (int) $row->count])" color="bg-purple-500" />
            <div class="mt-4 space-y-2 border-t border-gray-100 pt-4 text-sm dark:border-zinc-800">
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">{{ __('admin.qr_dynamic_vs_static') }}</span>
                    <span class="tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($stats['dynamic_qr_codes']) }} / {{ number_format($stats['static_qr_codes']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">{{ __('admin.logos_uploaded') }}</span>
                    <span class="tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($stats['qr_with_logo']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">{{ __('admin.categories') }}</span>
                    <span class="tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($stats['categories']) }}</span>
                </div>
            </div>
        </x-admin.panel>

        <x-admin.panel :title="__('admin.short_links')">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.total') }}</dt><dd class="tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($stats['short_links']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.links_active') }}</dt><dd class="tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($stats['links_active']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.links_expired') }}</dt><dd class="tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($stats['links_expired']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.links_password') }}</dt><dd class="tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($stats['links_password']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.links_capped') }}</dt><dd class="tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($stats['links_capped']) }}</dd></div>
            </dl>
            <dl class="mt-4 space-y-3 border-t border-gray-100 pt-4 text-sm dark:border-zinc-800">
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.teams') }}</dt><dd class="tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($stats['teams']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.team_members') }}</dt><dd class="tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($stats['team_members']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.team_qr_codes') }}</dt><dd class="tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($stats['team_qr_codes']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.custom_domains') }}</dt><dd class="tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($stats['custom_domains']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('admin.api_tokens') }}</dt><dd class="tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($stats['api_tokens']) }}</dd></div>
            </dl>
        </x-admin.panel>
    </div>

    {{-- Where the scans come from --}}
    <div>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.scan_breakdown') }}</h2>
            <div class="flex gap-2">
                @foreach(['7' => __('admin.last_7_days'), '30' => __('admin.last_30_days'), '90' => '90d'] as $value => $label)
                    <button type="button" wire:click="$set('period', '{{ $value }}')"
                            @class([
                                'rounded-lg px-3 py-1.5 text-xs font-medium transition',
                                'bg-primary-600 text-white' => $period === $value,
                                'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-zinc-900 dark:text-gray-300 dark:ring-zinc-700 dark:hover:bg-zinc-800' => $period !== $value,
                            ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.panel :title="__('admin.top_countries')">
                <x-admin.breakdown :rows="$countries->map(fn ($row) => ['label' => $row->country, 'value' => (int) $row->count])" />
            </x-admin.panel>
            <x-admin.panel :title="__('admin.top_devices')">
                <x-admin.breakdown :rows="$devices->map(fn ($row) => ['label' => ucfirst($row->device_type), 'value' => (int) $row->count])" color="bg-emerald-500" />
            </x-admin.panel>
            <x-admin.panel :title="__('admin.top_browsers')">
                <x-admin.breakdown :rows="$browsers->map(fn ($row) => ['label' => $row->browser, 'value' => (int) $row->count])" color="bg-purple-500" />
            </x-admin.panel>
            <x-admin.panel title="OS">
                <x-admin.breakdown :rows="$operatingSystems->map(fn ($row) => ['label' => $row->os, 'value' => (int) $row->count])" color="bg-amber-500" />
            </x-admin.panel>
        </div>
    </div>

    {{-- Heaviest QR codes on the platform --}}
    <x-admin.panel :title="__('admin.top_qr_global')" padding="p-0">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-zinc-800">
                <thead class="bg-gray-50 dark:bg-zinc-900/60">
                    <tr>
                        <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_name') }}</th>
                        <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.owner') }}</th>
                        <th class="px-5 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_type') }}</th>
                        <th class="px-5 py-2 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.column_scans') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                    @forelse($topQrCodes as $qr)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50" wire:key="top-qr-{{ $qr->id }}">
                            <td class="px-5 py-2.5 font-medium text-gray-900 dark:text-gray-100">{{ $qr->name }}</td>
                            <td class="px-5 py-2.5">
                                @if($qr->user)
                                    <a href="{{ route('admin.users.show', $qr->user) }}" wire:navigate class="text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                        {{ $qr->user->name }}
                                    </a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-gray-600 dark:text-gray-300">{{ $qr->type->value }}</td>
                            <td class="px-5 py-2.5 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($qr->total_scans) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('admin.no_data') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.panel>
</div>
