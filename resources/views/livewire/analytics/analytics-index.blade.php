<div>
    <h1 class="text-2xl font-bold text-gray-900">{{ __('nav.analytics') }}</h1>

    <div class="mt-6 grid gap-6 lg:grid-cols-4">
        {{-- QR Code selector --}}
        <div class="lg:col-span-1">
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-900/5">
                <h3 class="text-sm font-semibold text-gray-700">Select QR Code</h3>
                <div class="mt-3 space-y-2">
                    @forelse($qrCodes as $qr)
                        <button wire:click="$set('qrCodeId', {{ $qr->id }})"
                                class="flex w-full items-center justify-between rounded-lg p-2 text-left text-sm transition {{ $qrCodeId === $qr->id ? 'bg-primary-50 text-primary-700 ring-1 ring-primary-200' : 'hover:bg-gray-50' }}">
                            <span class="truncate">{{ $qr->name }}</span>
                            <span class="ml-2 shrink-0 text-xs text-gray-500">{{ number_format($qr->total_scans) }}</span>
                        </button>
                    @empty
                        <p class="text-sm text-gray-500">No dynamic QR codes yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Analytics content --}}
        <div class="lg:col-span-3">
            @if($selectedQr)
                {{-- Period selector --}}
                <div class="mb-4 flex gap-2">
                    @foreach(['7d' => '7 Days', '30d' => '30 Days', '90d' => '90 Days'] as $p => $label)
                        <button wire:click="$set('period', '{{ $p }}')"
                                class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ $period === $p ? 'bg-primary-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Stats cards --}}
                <div class="mb-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                        <p class="text-sm text-gray-500">Total Scans</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($scanTotals['total']) }}</p>
                    </div>
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                        <p class="text-sm text-gray-500">Unique Scans</p>
                        <p class="text-3xl font-bold text-gray-900">{{ number_format($scanTotals['unique']) }}</p>
                    </div>
                </div>

                {{-- Chart --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                    <h3 class="text-sm font-semibold text-gray-700">Scans Over Time</h3>
                    <div class="mt-4" x-data="analyticsChart()" x-init="init(@js($dailyScans))">
                        <canvas x-ref="chart" height="250"></canvas>
                    </div>
                </div>

                {{-- Breakdown grids --}}
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {{-- Devices --}}
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                        <h3 class="text-sm font-semibold text-gray-700">Devices</h3>
                        <div class="mt-3 space-y-2">
                            @forelse($devices as $device)
                                @php $pct = $scanTotals['total'] > 0 ? round(($device->count / $scanTotals['total']) * 100) : 0; @endphp
                                <div class="flex items-center justify-between text-sm">
                                    <span class="capitalize text-gray-600">{{ $device->device_type }}</span>
                                    <span class="font-medium text-gray-900">{{ number_format($device->count) }} ({{ $pct }}%)</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-gray-100">
                                    <div class="h-1.5 rounded-full bg-primary-500" style="width: {{ $pct }}%"></div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-400">No data yet</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Browsers --}}
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                        <h3 class="text-sm font-semibold text-gray-700">Browsers</h3>
                        <div class="mt-3 space-y-2">
                            @forelse($browsers as $browser)
                                @php $pct = $scanTotals['total'] > 0 ? round(($browser->count / $scanTotals['total']) * 100) : 0; @endphp
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">{{ $browser->browser }}</span>
                                    <span class="font-medium text-gray-900">{{ number_format($browser->count) }} ({{ $pct }}%)</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-gray-100">
                                    <div class="h-1.5 rounded-full bg-blue-500" style="width: {{ $pct }}%"></div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-400">No data yet</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- OS --}}
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                        <h3 class="text-sm font-semibold text-gray-700">Operating Systems</h3>
                        <div class="mt-3 space-y-2">
                            @forelse($osStat as $os)
                                @php $pct = $scanTotals['total'] > 0 ? round(($os->count / $scanTotals['total']) * 100) : 0; @endphp
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">{{ $os->os }}</span>
                                    <span class="font-medium text-gray-900">{{ number_format($os->count) }} ({{ $pct }}%)</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-gray-100">
                                    <div class="h-1.5 rounded-full bg-green-500" style="width: {{ $pct }}%"></div>
                                </div>
                            @empty
                                <p class="text-xs text-gray-400">No data yet</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Referrers & Countries --}}
                <div class="mt-6 grid gap-6 sm:grid-cols-2">
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                        <h3 class="text-sm font-semibold text-gray-700">Top Referrers</h3>
                        <div class="mt-3 space-y-2">
                            @forelse($referrers as $ref)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="truncate text-gray-600" title="{{ $ref->referrer }}">{{ \Illuminate\Support\Str::limit($ref->referrer, 40) }}</span>
                                    <span class="ml-2 shrink-0 font-medium text-gray-900">{{ number_format($ref->count) }}</span>
                                </div>
                            @empty
                                <p class="text-xs text-gray-400">No referrer data yet</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                        <h3 class="text-sm font-semibold text-gray-700">Countries</h3>
                        <div class="mt-3 space-y-2">
                            @forelse($countries as $country)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">{{ $country->country ?: 'Unknown' }}</span>
                                    <span class="font-medium text-gray-900">{{ number_format($country->count) }}</span>
                                </div>
                            @empty
                                <p class="text-xs text-gray-400">No location data yet</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @else
                <div class="flex items-center justify-center rounded-xl border-2 border-dashed border-gray-300 p-12">
                    <div class="text-center">
                        <x-icon name="chart-bar" class="mx-auto h-12 w-12 text-gray-400" />
                        <p class="mt-4 text-sm text-gray-500">Select a QR code to view analytics</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
function analyticsChart() {
    return {
        chart: null,
        init(data) {
            const ctx = this.$refs.chart.getContext('2d');
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.label),
                    datasets: [
                        {
                            label: 'Total Scans',
                            data: data.map(d => d.total),
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.3,
                        },
                        {
                            label: 'Unique Scans',
                            data: data.map(d => d.unique),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.3,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                        x: { ticks: { maxTicksLimit: 10 } }
                    }
                }
            });
        }
    };
}
</script>
@endpush
